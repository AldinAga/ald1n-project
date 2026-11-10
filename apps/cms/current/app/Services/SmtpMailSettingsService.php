<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
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
 * @author Ald1n <https://ald1n.com>
 */
final class SmtpMailSettingsService
{
    public const ENCRYPTIONS = ['tls', 'ssl', 'none'];
    public const DEFAULT_TIMEOUT = 15;
    public const TEST_TIMEOUT_MAX = 10;

    /** Result codes for the test e-mail; raw transport errors are never stored. */
    public const RESULT_LABELS = [
        'ok' => 'Probni e-mail je predat SMTP serveru.',
        'connection_refused' => 'Server je odbio vezu. Proverite host i port.',
        'timeout' => 'Isteklo je vreme za povezivanje sa serverom.',
        'dns' => 'Host nije pronađen. Proverite naziv servera.',
        'tls' => 'TLS/SSL veza nije uspostavljena. Proverite enkripciju i port.',
        'auth' => 'Prijava na SMTP server nije uspela. Proverite korisničko ime i lozinku.',
        'sender_rejected' => 'Server je odbio pošiljaoca ili primaoca poruke.',
        'blocked_host' => 'Host pokazuje na zabranjenu (link-local) adresu.',
        'incomplete' => 'SMTP podešavanja nisu potpuna.',
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
            'timeout' => $timeout === false ? null : $timeout,
            'password_stored' => $passwordStored,
            'password_readable' => $passwordStored && $this->settings->getSecret('smtp_password') !== null,
            'last_test_at' => (string) ($all['smtp_last_test_at'] ?? ''),
            'last_test_result' => (string) ($all['smtp_last_test_result'] ?? ''),
            'last_test_by' => (string) ($all['smtp_last_test_by'] ?? ''),
        ];
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

        $password = null;
        if ($stored['username'] !== '') {
            $password = $this->settings->getSecret('smtp_password');
            if ($password === null) {
                return ['ok' => false, 'reason' => $stored['password_stored'] ? 'password_unreadable' : 'incomplete', 'config' => null];
            }
        }

        $encryption = $stored['encryption'];
        $config = [
            'transport' => 'smtp',
            'url' => null,
            'scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'host' => filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? '['.$host.']' : $host,
            'port' => $stored['port'],
            'username' => $stored['username'] !== '' ? $stored['username'] : null,
            'password' => $password,
            'timeout' => $timeout ?? ($stored['timeout'] ?? self::DEFAULT_TIMEOUT),
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
     *
     * @return array{active:bool,reason:string,config:?array<string,mixed>}
     */
    public function runtimeConfig(): array
    {
        try {
            if (!$this->stored()['enabled']) {
                return ['active' => false, 'reason' => 'disabled', 'config' => null];
            }
            $connection = $this->connectionConfig();

            return ['active' => $connection['ok'], 'reason' => $connection['reason'], 'config' => $connection['config']];
        } catch (Throwable) {
            return ['active' => false, 'reason' => 'unavailable', 'config' => null];
        }
    }

    /** Sends one fixed message to the SuperAdmin's own address; returns a result code. */
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
            $timeout = min($stored['timeout'] ?? self::TEST_TIMEOUT_MAX, self::TEST_TIMEOUT_MAX);
            $connection = $this->connectionConfig($timeout);
            if (!$connection['ok'] || $connection['config'] === null) {
                return $connection['reason'];
            }
            if (self::resolvesToBlockedAddress($stored['host'])) {
                return 'blocked_host';
            }

            $fromName = trim((string) config('mail.from.name'));
            $message = (new Email())
                ->from(new Address($fromAddress, $fromName))
                ->to(new Address($recipient))
                ->subject('Probni e-mail (SMTP) - '.((string) config('app.name', 'Ald1n CMS')))
                ->text("Ovo je probna poruka sa stranice Podešavanja > E-mail (SMTP).\n"
                    ."Ako ste je primili, SMTP server prihvata poruke sa sačuvanim podešavanjima.\n");

            $this->transports->create($connection['config'])->send($message);

            return 'ok';
        } catch (Throwable $exception) {
            return self::classify($exception);
        }
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

        return preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)(?:\.(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?))*$/i', $host) === 1;
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
            // IPv4-mapped (::ffff:a.b.c.d): judge the embedded IPv4 address.
            if (substr($packed, 0, 12) === str_repeat("\0", 10)."\xff\xff") {
                $packed = substr($packed, 12);
            } else {
                return false;
            }
        }
        $first = ord($packed[0]);
        $second = ord($packed[1]);

        // 169.254.0.0/16 link-local (incl. 169.254.169.254 metadata), 0.0.0.0/8.
        return ($first === 169 && $second === 254) || $first === 0;
    }

    /** Checked at test time only: a hostname that resolves into a blocked range is refused. */
    public static function resolvesToBlockedAddress(string $host): bool
    {
        $host = trim($host);
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::blockedIp($host);
        }
        $addresses = @gethostbynamel($host);
        if (!is_array($addresses)) {
            return false;
        }
        foreach ($addresses as $address) {
            if (self::blockedIp($address)) {
                return true;
            }
        }

        return false;
    }
}
