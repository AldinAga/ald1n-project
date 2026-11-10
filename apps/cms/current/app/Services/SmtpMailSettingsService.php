<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Database SMTP transport settings (SuperAdmin page "E-mail (SMTP)").
 *
 * The password is stored only through SettingsService::putSecret() (Crypt)
 * and is never returned by the public read methods of this class except
 * runtimeConfig(), which feeds the in-memory mailer configuration.
 *
 * The database transport is used only while the switch is ON and the last
 * test e-mail succeeded for exactly the saved connection (host, port,
 * encryption, username and stored password). Any connection edit
 * invalidates that result.
 *
 * Accepted risk: a SuperAdmin can point the host at any non-link-local
 * address and use the test e-mail as a TCP probe (port scan). Private and
 * loopback ranges stay allowed because cPanel exim runs on the same host;
 * the probe is limited by the "smtp-test" rate limit (3/min, 10/h per user),
 * password re-confirmation and an audit entry for every attempt.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SmtpMailSettingsService
{
    public const ENCRYPTIONS = ['tls', 'ssl', 'none'];
    public const DEFAULT_TIMEOUT = 10;
    public const TIMEOUT_MAX = 10;
    public const TEST_TIMEOUT_MAX = self::TIMEOUT_MAX;

    /** Result codes for the test e-mail; raw transport errors are never stored. */
    public const RESULT_LABELS = [
        'ok' => 'Probni e-mail je predat SMTP serveru.',
        'connection_refused' => 'Server je odbio vezu. Proverite host i port.',
        'timeout' => 'Isteklo je vreme za povezivanje sa serverom.',
        'dns' => 'Host nije pronađen. Proverite naziv servera.',
        'tls' => 'TLS/SSL veza nije uspostavljena. Proverite enkripciju i port.',
        'auth' => 'Prijava na SMTP server nije uspela. Proverite korisničko ime i lozinku.',
        'sender_rejected' => 'Pošiljalac odbijen: server nije prihvatio adresu MAIL_FROM_ADDRESS (ili adresu primaoca).',
        'registration_sender_rejected' => 'Pošiljalac odbijen: server nije prihvatio adresu pošiljaoca potvrda registracije (stranica Javna prodavnica).',
        'blocked_host' => 'Host pokazuje na zabranjenu (link-local ili metadata) adresu.',
        'incomplete' => 'SMTP podešavanja nisu potpuna.',
        'password_missing' => 'Lozinka nije sačuvana. Unesite lozinku pa pošaljite probni e-mail.',
        'insecure_auth' => 'Bez enkripcije lozinka bi išla nešifrovano. Izaberite SSL ili TLS.',
        'password_unreadable' => 'Sačuvana lozinka ne može da se dešifruje. Unesite je ponovo.',
        'invalid_recipient' => 'Vaš nalog nema ispravnu e-mail adresu.',
        'invalid_from' => 'Podrazumevani pošiljalac (MAIL_FROM_ADDRESS) nije ispravan.',
        'unknown' => 'Slanje nije uspelo. Server je vratio grešku koju ne prepoznajemo.',
    ];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly SmtpTestTransportFactory $transports,
    ) {}

    /**
     * Non-secret view of the stored values. Never contains the password.
     *
     * @return array{enabled:bool,host:string,port:?int,encryption:string,username:string,timeout:?int,password_stored:bool,password_readable:bool,last_test_at:string,last_test_result:string,last_test_by:string}
     */
    public function stored(): array
    {
        $all = $this->settings->all();
        $port = filter_var($all['smtp_port'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        $timeout = filter_var($all['smtp_timeout'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 60]]);
        $encryption = (string) ($all['smtp_encryption'] ?? '');
        $passwordStored = $this->settings->hasSecret('smtp_password');

        return [
            'enabled' => ($all['smtp_enabled'] ?? '0') === '1',
            'host' => (string) ($all['smtp_host'] ?? ''),
            'port' => $port === false ? null : $port,
            'encryption' => in_array($encryption, self::ENCRYPTIONS, true) ? $encryption : '',
            'username' => (string) ($all['smtp_username'] ?? ''),
            'timeout' => $timeout === false ? null : min($timeout, self::TIMEOUT_MAX),
            'password_stored' => $passwordStored,
            'password_readable' => $passwordStored && $this->settings->getSecret('smtp_password') !== null,
            'last_test_at' => (string) ($all['smtp_last_test_at'] ?? ''),
            'last_test_result' => (string) ($all['smtp_last_test_result'] ?? ''),
            'last_test_by' => (string) ($all['smtp_last_test_by'] ?? ''),
        ];
    }

    /**
     * Identifies what the test e-mail verified: host, port, encryption,
     * username, a digest of the stored password ciphertext, MAIL_FROM_ADDRESS
     * and the registration sender identity and approval. Changing any of them
     * voids a passing test. Read straight from settings (no mailer lookups),
     * because the runtime configurator calls this on every apply.
     */
    public function connectionFingerprint(): string
    {
        $stored = $this->stored();

        return hash('sha256', (string) json_encode([
            mb_strtolower(trim($stored['host'])),
            $stored['port'],
            $stored['encryption'],
            trim($stored['username']),
            $this->settings->secretDigest('smtp_password'),
            mb_strtolower(trim((string) config('mail.from.address'))),
            [
                mb_strtolower(trim((string) $this->settings->get('public_registration_sender_address', ''))),
                trim((string) $this->settings->get('public_registration_sender_name', '')),
                (string) $this->settings->get('public_registration_sender_digest', ''),
                (string) $this->settings->get('public_registration_sender_approved_by', ''),
                (string) $this->settings->get('public_registration_sender_approved_at', ''),
            ],
        ]));
    }

    /** True when the last test e-mail succeeded for exactly the saved connection. */
    public function testPassedForCurrentSettings(): bool
    {
        if ((string) $this->settings->get('smtp_last_test_result', '') !== 'ok') {
            return false;
        }
        $fingerprint = (string) $this->settings->get('smtp_last_test_fingerprint', '');

        return $fingerprint !== '' && hash_equals($fingerprint, $this->connectionFingerprint());
    }

    /**
     * Mailer configuration for the stored values, independent of the ON switch.
     *
     * @return array{ok:bool,reason:string,config:?array<string,mixed>}
     */
    public function connectionConfig(?int $timeout = null): array
    {
        $stored = $this->stored();
        $host = $stored['host'];
        if (!self::validHost($host) || self::blockedHost($host)
            || $stored['port'] === null || $stored['encryption'] === '') {
            return ['ok' => false, 'reason' => 'incomplete', 'config' => null];
        }
        $encryption = $stored['encryption'];
        if ($encryption === 'none' && $stored['username'] !== '' && !self::isLoopbackHost($host)) {
            return ['ok' => false, 'reason' => 'insecure_auth', 'config' => null];
        }

        $password = null;
        if ($stored['username'] !== '') {
            if (!$stored['password_stored']) {
                return ['ok' => false, 'reason' => 'password_missing', 'config' => null];
            }
            $password = $this->settings->getSecret('smtp_password');
            if ($password === null) {
                return ['ok' => false, 'reason' => 'password_unreadable', 'config' => null];
            }
        }

        $config = [
            'transport' => 'smtp',
            'url' => null,
            'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'host' => filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? '['.$host.']' : $host,
            'port' => $stored['port'],
            'username' => $stored['username'] !== '' ? $stored['username'] : null,
            'password' => $password,
            'timeout' => max(1, min($timeout ?? ($stored['timeout'] ?? self::DEFAULT_TIMEOUT), self::TIMEOUT_MAX)),
            // tls: STARTTLS is mandatory; none: never upgrade; ssl: implicit TLS (smtps).
            'auto_tls' => $encryption !== 'none',
            'require_tls' => $encryption === 'tls',
        ];
        $localDomain = config('mail.mailers.smtp.local_domain');
        if (is_string($localDomain) && $localDomain !== '') {
            $config['local_domain'] = $localDomain;
        }

        return ['ok' => true, 'reason' => 'ok', 'config' => $config];
    }

    /**
     * What the running process should use. Any failure (missing table, cache,
     * decrypt) means "keep the .env mailer"; this method never throws.
     * While the switch is OFF only one settings read happens (one table check).
     *
     * @return array{active:bool,reason:string,config:?array<string,mixed>}
     */
    public function runtimeConfig(): array
    {
        try {
            if ($this->settings->get('smtp_enabled', '0') !== '1') {
                return ['active' => false, 'reason' => 'disabled', 'config' => null];
            }
            $connection = $this->connectionConfig();
            if (!$connection['ok']) {
                return ['active' => false, 'reason' => $connection['reason'], 'config' => null];
            }
            if (!$this->testPassedForCurrentSettings()) {
                return ['active' => false, 'reason' => 'not_tested', 'config' => null];
            }

            return ['active' => true, 'reason' => 'ok', 'config' => $connection['config']];
        } catch (Throwable) {
            return ['active' => false, 'reason' => 'unavailable', 'config' => null];
        }
    }

    /**
     * Sends fixed messages to the SuperAdmin's own address with the saved
     * settings: one From MAIL_FROM_ADDRESS and, when an approved public
     * registration sender exists and differs, one From that address.
     * Returns a result code.
     */
    public function sendTest(User $user): string
    {
        $recipient = trim((string) $user->email);
        if ($recipient === '' || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false
            || preg_match('/[\x00-\x20\x7f]/', $recipient) === 1) {
            return 'invalid_recipient';
        }
        $fromAddress = trim((string) config('mail.from.address'));
        if ($fromAddress === '' || filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false) {
            return 'invalid_from';
        }

        try {
            $stored = $this->stored();
            // Same timeout as real mail (at most TIMEOUT_MAX seconds).
            $connection = $this->connectionConfig();
            if (!$connection['ok'] || $connection['config'] === null) {
                return $connection['reason'];
            }
            if (self::resolvesToBlockedAddress($stored['host'])) {
                return 'blocked_host';
            }
            $registrationSender = $this->approvedRegistrationSender($fromAddress);
            $transport = $this->transports->create($connection['config']);
        } catch (Throwable $exception) {
            return self::classify($exception);
        }

        $result = $this->sendOne($transport, new Address($fromAddress, trim((string) config('mail.from.name'))), $recipient,
            'Ovo je probna poruka sa stranice Podešavanja > E-mail (SMTP), pošiljalac MAIL_FROM_ADDRESS.');
        if ($result !== 'ok' || $registrationSender === null) {
            return $result;
        }

        $result = $this->sendOne($transport, $registrationSender, $recipient,
            'Ovo je druga probna poruka, sa pošiljaocem koji se koristi za potvrde registracije Kupca.');

        return $result === 'sender_rejected' ? 'registration_sender_rejected' : $result;
    }

    /** Maps a transport failure to a short code. The message itself is discarded. */
    public static function classify(Throwable $exception): string
    {
        $text = '';
        for ($current = $exception, $depth = 0; $current !== null && $depth < 5; $current = $current->getPrevious(), $depth++) {
            $text .= ' '.$current->getMessage();
        }
        $text = mb_strtolower($text);

        return match (true) {
            str_contains($text, 'getaddrinfo') || str_contains($text, 'name or service not known')
                || str_contains($text, 'no such host') || str_contains($text, 'php_network_getaddresses') => 'dns',
            str_contains($text, 'timed out') || str_contains($text, 'timeout') => 'timeout',
            str_contains($text, 'connection refused') => 'connection_refused',
            str_contains($text, 'authenticat') || str_contains($text, 'code "535"') || str_contains($text, 'code "534"') => 'auth',
            str_contains($text, 'tls') || str_contains($text, 'ssl') || str_contains($text, 'crypto')
                || str_contains($text, 'certificate') => 'tls',
            preg_match('/code "5(50|51|53|54)"/', $text) === 1 || str_contains($text, 'sender')
                || str_contains($text, 'relay') || str_contains($text, 'mail from') => 'sender_rejected',
            str_contains($text, 'could not be established') || str_contains($text, 'unable to connect') => 'connection_refused',
            default => 'unknown',
        };
    }

    public static function label(string $code): string
    {
        return self::RESULT_LABELS[$code] ?? self::RESULT_LABELS['unknown'];
    }

    /** sha256 of host:port:username for audit correlation; never includes the password. */
    public static function targetFingerprint(string $host, ?int $port, string $username): string
    {
        return hash('sha256', mb_strtolower(trim($host)).':'.(string) ($port ?? '').':'.trim($username));
    }

    public static function validHost(string $host): bool
    {
        $host = trim($host);
        if ($host === '' || strlen($host) > 253) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }
        // Numeric forms that resolvers may read as an IPv4 address
        // (2852039166, 0251.0376.0251.0376, 0xa9.0xfe.0xa9.0xfe, 169.254.43518).
        if (preg_match('/^[0-9.]+$/', $host) === 1 || preg_match('/(?:^|\.)0x[0-9a-f]*(?:\.|$)/i', $host) === 1) {
            return false;
        }

        return preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/i', $host) === 1;
    }

    /** Loopback only: 127.0.0.0/8, ::1 and "localhost". */
    public static function isLoopbackHost(string $host): bool
    {
        $host = mb_strtolower(trim($host));
        if ($host === 'localhost') {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return str_starts_with($host, '127.');
        }
        $packed = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? @inet_pton($host) : false;

        return $packed !== false && $packed === str_repeat("\0", 15)."\x01";
    }

    /** Link-local / cloud-metadata targets are refused (SSRF guard). */
    public static function blockedHost(string $host): bool
    {
        $host = mb_strtolower(trim($host));
        if (in_array($host, ['metadata', 'metadata.google.internal', 'instance-data', 'instance-data.ec2.internal'], true)) {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::blockedIp($host);
        }

        return false;
    }

    /**
     * Blocks link-local and metadata addresses, also when an IPv4 address is
     * embedded in IPv6 (::ffff:0:0/96 mapped, 64:ff9b::/96 NAT64, 2002::/16 6to4).
     * Private and loopback ranges stay allowed (local exim).
     */
    public static function blockedIp(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return true;
        }
        if (strlen($packed) === 16) {
            $bytes = array_values(unpack('C*', $packed) ?: []);
            // fe80::/10 link-local
            if ($bytes[0] === 0xfe && ($bytes[1] & 0xc0) === 0x80) {
                return true;
            }
            // :: unspecified
            if (trim($packed, "\0") === '') {
                return true;
            }
            // fd00:ec2::254, the AWS instance metadata service over IPv6.
            if ($packed === inet_pton('fd00:ec2::254')) {
                return true;
            }
            if (substr($packed, 0, 12) === str_repeat("\0", 10)."\xff\xff") {
                $packed = substr($packed, 12);
            } elseif (substr($packed, 0, 12) === "\x00\x64\xff\x9b".str_repeat("\0", 8)) {
                $packed = substr($packed, 12);
            } elseif (substr($packed, 0, 2) === "\x20\x02") {
                $packed = substr($packed, 2, 4);
            } else {
                return false;
            }
        }
        $first = ord($packed[0]);
        $second = ord($packed[1]);

        // 169.254.0.0/16 link-local (incl. 169.254.169.254 metadata), 0.0.0.0/8.
        return ($first === 169 && $second === 254) || $first === 0;
    }

    /**
     * Checked at test time only: a hostname with any A or AAAA record in a
     * blocked range is refused.
     */
    public static function resolvesToBlockedAddress(string $host): bool
    {
        $host = trim($host);
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::blockedIp($host);
        }
        if (!self::validHost($host)) {
            return true;
        }

        $addresses = [];
        try {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);
            foreach (is_array($records) ? $records : [] as $record) {
                foreach (['ip', 'ipv6'] as $key) {
                    if (isset($record[$key]) && is_string($record[$key])) {
                        $addresses[] = $record[$key];
                    }
                }
            }
        } catch (Throwable) {
            // Resolution errors surface as a DNS failure when connecting.
        }
        $ipv4 = @gethostbynamel($host);
        if (is_array($ipv4)) {
            $addresses = array_merge($addresses, $ipv4);
        }
        foreach ($addresses as $address) {
            if (self::blockedIp($address)) {
                return true;
            }
        }

        return false;
    }

    private function approvedRegistrationSender(string $fromAddress): ?Address
    {
        try {
            $status = app(PublicRegistrationMailSenderService::class)->status();
        } catch (Throwable) {
            return null;
        }
        if (!$status['approved'] || $status['address'] === ''
            || mb_strtolower($status['address']) === mb_strtolower($fromAddress)) {
            return null;
        }

        return new Address($status['address'], $status['name']);
    }

    private function sendOne(TransportInterface $transport, Address $from, string $recipient, string $line): string
    {
        try {
            $message = (new Email())
                ->from($from)
                ->to(new Address($recipient))
                ->subject('Probni e-mail (SMTP) - '.((string) config('app.name', 'Ald1n CMS')))
                ->text($line."\nAko ste je primili, SMTP server prihvata poruke sa sačuvanim podešavanjima.\n");
            $transport->send($message);

            return 'ok';
        } catch (Throwable $exception) {
            return self::classify($exception);
        }
    }
}
