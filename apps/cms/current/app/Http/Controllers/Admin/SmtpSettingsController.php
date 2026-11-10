<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MailRuntimeConfigurator;
use App\Services\SettingsService;
use App\Services\SmtpMailSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * SuperAdmin page "E-mail (SMTP)". The password is write-only: it is never
 * rendered, flashed, logged or written to the audit log.
 *
 * Connection changes, turning the switch ON and the test e-mail require the
 * SuperAdmin's current password. Changing the server (host, port, encryption
 * or username) without entering the SMTP password again clears the stored
 * one, so a stolen session cannot send it to a server of its choice. The
 * switch stays ON only after a successful test of exactly the saved
 * connection.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SmtpSettingsController extends Controller
{
    /** Fields that identify the server the password would be sent to. */
    private const SERVER_FIELDS = ['host', 'port', 'encryption', 'username'];

    private const TEST_FIELDS = [
        'smtp_last_test_at' => '',
        'smtp_last_test_result' => '',
        'smtp_last_test_by' => '',
        'smtp_last_test_fingerprint' => '',
    ];

    private const SERVER_CHANGED_NOTICE = 'Zbog promene servera lozinka mora ponovo da se unese.';

    public function index(Request $request, SmtpMailSettingsService $smtp, MailRuntimeConfigurator $runtime): View
    {
        $this->authorizeOwner($request);
        $source = $runtime->apply();
        $stored = $smtp->stored();
        $tester = $stored['last_test_by'] !== ''
            ? User::query()->whereKey((int) $stored['last_test_by'])->first()
            : null;

        return view('admin.settings.smtp', [
            'smtp' => $stored,
            'effectiveSource' => $source,
            'fallbackReason' => $runtime->reason(),
            'envMailer' => $runtime->envDefaultMailer(),
            'fromAddress' => (string) config('mail.from.address'),
            'fromName' => (string) config('mail.from.name'),
            'testRecipient' => (string) $request->user()->email,
            'testPassed' => $smtp->testPassedForCurrentSettings(),
            'lastTestLabel' => $stored['last_test_result'] !== '' ? SmtpMailSettingsService::label($stored['last_test_result']) : null,
            'lastTestBy' => $tester?->displayName(),
        ]);
    }

    public function update(
        Request $request,
        SettingsService $settings,
        SmtpMailSettingsService $smtp,
        MailRuntimeConfigurator $runtime,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorizeOwner($request);
        $before = $smtp->stored();
        $testPassed = $smtp->testPassedForCurrentSettings();
        $enabled = $request->boolean('smtp_enabled');

        $validator = Validator::make($request->all(), [
            'smtp_host' => ['nullable', 'string', 'max:253'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['nullable', 'string', Rule::in(SmtpMailSettingsService::ENCRYPTIONS)],
            'smtp_username' => ['nullable', 'string', 'max:254'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_timeout' => ['nullable', 'integer', 'between:1,'.SmtpMailSettingsService::TIMEOUT_MAX],
            'current_password' => ['nullable', 'string', 'max:255'],
        ], [
            'smtp_host.max' => 'Host može imati najviše 253 znaka.',
            'smtp_host.string' => 'Host mora biti tekst.',
            'smtp_port.integer' => 'Port mora biti ceo broj od 1 do 65535.',
            'smtp_port.between' => 'Port mora biti ceo broj od 1 do 65535.',
            'smtp_encryption.in' => 'Izaberite enkripciju: TLS (STARTTLS), SSL ili bez enkripcije.',
            'smtp_encryption.string' => 'Izaberite enkripciju: TLS (STARTTLS), SSL ili bez enkripcije.',
            'smtp_username.max' => 'Korisničko ime može imati najviše 254 znaka.',
            'smtp_username.string' => 'Korisničko ime mora biti tekst.',
            'smtp_password.max' => 'Lozinka može imati najviše 255 znakova.',
            'smtp_password.string' => 'Lozinka mora biti tekst.',
            'smtp_timeout.integer' => 'Vreme čekanja mora biti ceo broj sekundi od 1 do 10.',
            'smtp_timeout.between' => 'Vreme čekanja mora biti ceo broj sekundi od 1 do 10.',
            'current_password.string' => 'Unesite vašu trenutnu lozinku.',
            'current_password.max' => 'Trenutna lozinka nije ispravna.',
        ]);

        $validator->after(function ($validator) use ($request, $enabled): void {
            $errors = $validator->errors();
            $host = mb_strtolower(trim((string) $request->input('smtp_host', '')));
            $username = trim((string) $request->input('smtp_username', ''));
            $password = (string) $request->input('smtp_password', '');

            if ($host !== '' && !$errors->has('smtp_host')) {
                if (!SmtpMailSettingsService::validHost($host)) {
                    $errors->add('smtp_host', 'Host mora biti naziv servera (npr. mail.example.com) ili IP adresa, bez http:// i porta.');
                } elseif (SmtpMailSettingsService::blockedHost($host)) {
                    $errors->add('smtp_host', 'Ovaj host nije dozvoljen (link-local ili metadata adresa).');
                }
            }
            if ($enabled) {
                if ($host === '' && !$errors->has('smtp_host')) {
                    $errors->add('smtp_host', 'Host je obavezan kada je SMTP iz baze uključen.');
                }
                if (trim((string) $request->input('smtp_port', '')) === '' && !$errors->has('smtp_port')) {
                    $errors->add('smtp_port', 'Port je obavezan kada je SMTP iz baze uključen.');
                }
                if (trim((string) $request->input('smtp_encryption', '')) === '' && !$errors->has('smtp_encryption')) {
                    $errors->add('smtp_encryption', 'Izaberite enkripciju: TLS (STARTTLS), SSL ili bez enkripcije.');
                }
            }
            if ((string) $request->input('smtp_encryption', '') === 'none' && $username !== ''
                && !$errors->has('smtp_encryption') && !SmtpMailSettingsService::isLoopbackHost($host)) {
                $errors->add('smtp_encryption', 'Bez enkripcije bi korisničko ime i lozinka išli nešifrovano. Izaberite SSL ili TLS (bez enkripcije je dozvoljeno samo za localhost).');
            }
            if ($username !== '' && preg_match('/[\x00-\x20\x7f]/', $username) === 1 && !$errors->has('smtp_username')) {
                $errors->add('smtp_username', 'Korisničko ime ne sme sadržati razmake ni kontrolne znakove.');
            }
            if ($password !== '' && !$errors->has('smtp_password')) {
                if (preg_match('/[\x00-\x1f\x7f]/', $password) === 1) {
                    $errors->add('smtp_password', 'Lozinka ne sme sadržati kontrolne znakove ni prelom reda.');
                } elseif (trim($password) === '') {
                    $errors->add('smtp_password', 'Lozinka ne može da se sastoji samo od razmaka.');
                }
            }
        });

        if ($validator->fails()) {
            return $this->backWithErrors($request, $validator->errors()->toArray());
        }

        $host = mb_strtolower(trim((string) $request->input('smtp_host', '')));
        $port = trim((string) $request->input('smtp_port', ''));
        $timeout = trim((string) $request->input('smtp_timeout', ''));
        $username = trim((string) $request->input('smtp_username', ''));
        // Not trimmed: spaces can be part of the password (TrimStrings skips it too).
        $password = (string) $request->input('smtp_password', '');
        $after = [
            'enabled' => $enabled,
            'host' => $host,
            'port' => $port === '' ? null : (int) $port,
            'encryption' => (string) $request->input('smtp_encryption', ''),
            'username' => $username,
            'timeout' => $timeout === '' ? null : (int) $timeout,
        ];

        $changed = [];
        foreach ($after as $field => $value) {
            if ($before[$field] !== $value) {
                $changed[] = $field;
            }
        }
        if ($password !== '') {
            $changed[] = 'password';
        }
        $serverChanged = array_intersect($changed, self::SERVER_FIELDS) !== [];
        $connectionChanged = $serverChanged || $password !== '';
        $enabling = $enabled && !$before['enabled'];

        if (($connectionChanged || $enabling) && !$this->currentPasswordConfirmed($request)) {
            $audit->log('settings.smtp.reauth_failed', 'E-mail (SMTP)', null, null, null, [
                'action' => 'update',
                'changed_fields' => $changed,
            ], user: $request->user(), level: 'warning');

            return $this->backWithErrors($request, [
                'current_password' => ['Za promenu servera, lozinke ili uključivanje unesite ispravnu trenutnu lozinku vašeg naloga.'],
            ]);
        }

        $values = [
            'smtp_host' => $host,
            'smtp_port' => $after['port'] === null ? '' : (string) $after['port'],
            'smtp_encryption' => $after['encryption'],
            'smtp_username' => $username,
            'smtp_timeout' => $after['timeout'] === null ? '' : (string) $after['timeout'],
        ];
        $notices = [];
        $passwordCleared = false;
        if ($serverChanged && $password === '' && $before['password_stored']) {
            // The stored password is never sent to a server it was not entered for.
            $values['smtp_password'] = '';
            $passwordCleared = true;
            $notices[] = self::SERVER_CHANGED_NOTICE;
        }

        $switchOn = $enabled;
        if ($connectionChanged) {
            // An older test result no longer describes these settings.
            $values += self::TEST_FIELDS;
            $switchOn = false;
        } elseif ($enabled && !$testPassed) {
            $switchOn = false;
        }
        if ($enabled && !$switchOn) {
            $notices[] = 'Prekidač „Koristi SMTP podešavanja iz baze“ je ostao isključen: prvo pošaljite uspešan probni e-mail sa sačuvanim podešavanjima, pa ga uključite.';
        } elseif ($before['enabled'] && !$switchOn && $connectionChanged) {
            $notices[] = 'Prekidač je isključen jer su podešavanja servera promenjena. Slanje koristi .env dok probni e-mail ne uspe i ponovo ga ne uključite.';
        }
        $values['smtp_enabled'] = $switchOn ? '1' : '0';
        if ($passwordCleared) {
            $changed[] = 'password_cleared';
        }

        $userId = (int) $request->user()->getAuthIdentifier();
        try {
            $settings->putMany($values, $userId);
            if ($password !== '') {
                $settings->putSecret('smtp_password', $password, $userId);
            }
        } finally {
            $settings->forgetCache();
        }

        $audit->log('settings.smtp.updated', 'E-mail (SMTP)', null, null, null, [
            'changed_fields' => $changed,
            'enabled' => $switchOn,
            'target_sha256' => SmtpMailSettingsService::targetFingerprint($host, $after['port'], $username),
        ], user: $request->user());

        $source = $runtime->apply();
        $message = 'SMTP podešavanja su sačuvana.';
        if ($switchOn && $source !== 'database') {
            $message .= ' Podešavanja nisu potpuna, pa se i dalje koristi slanje iz .env fajla.';
        } elseif ($source === 'database') {
            $message .= ' Slanje sada koristi SMTP server iz baze.';
        } else {
            $message .= ' Slanje i dalje koristi .env podešavanja.';
        }

        $redirect = redirect()->route('admin.settings.smtp.index')->with('status', $message);

        return $notices === [] ? $redirect : $redirect->with('smtp_notices', $notices);
    }

    public function clearPassword(
        Request $request,
        SettingsService $settings,
        SmtpMailSettingsService $smtp,
        MailRuntimeConfigurator $runtime,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorizeOwner($request);
        $before = $smtp->stored();
        try {
            $settings->putMany(['smtp_password' => '', 'smtp_enabled' => '0'] + self::TEST_FIELDS,
                (int) $request->user()->getAuthIdentifier());
        } finally {
            $settings->forgetCache();
        }
        $audit->log('settings.smtp.password_cleared', 'E-mail (SMTP)', null, null, null, [
            'had_stored_value' => $before['password_stored'],
            'target_sha256' => SmtpMailSettingsService::targetFingerprint($before['host'], $before['port'], $before['username']),
        ], user: $request->user());

        $runtime->apply();
        $message = 'Lozinka je obrisana.';
        if ($before['enabled']) {
            $message .= ' Prekidač je isključen i slanje koristi .env podešavanja dok ne unesete novu lozinku i probni e-mail ne uspe.';
        }

        return redirect()->route('admin.settings.smtp.index')->with('status', $message);
    }

    public function test(
        Request $request,
        SettingsService $settings,
        SmtpMailSettingsService $smtp,
        MailRuntimeConfigurator $runtime,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorizeOwner($request);
        /** @var User $user */
        $user = $request->user();
        $redirect = redirect()->route('admin.settings.smtp.index');

        if (!$this->currentPasswordConfirmed($request)) {
            $audit->log('settings.smtp.reauth_failed', 'E-mail (SMTP)', null, null, null, [
                'action' => 'test',
            ], user: $user, level: 'warning');

            return $redirect->withErrors(['smtp_test' => 'Probni e-mail nije poslat: unesite ispravnu trenutnu lozinku vašeg naloga.']);
        }

        $stored = $smtp->stored();
        // The result is bound to the connection as it was when the test started.
        $fingerprint = $smtp->connectionFingerprint();
        $result = $smtp->sendTest($user);

        $values = [
            'smtp_last_test_at' => now()->toISOString(),
            'smtp_last_test_result' => $result,
            'smtp_last_test_by' => (string) $user->id,
            'smtp_last_test_fingerprint' => $result === 'ok' ? $fingerprint : '',
        ];
        $switchedOff = $result !== 'ok' && $stored['enabled'];
        if ($switchedOff) {
            $values['smtp_enabled'] = '0';
        }
        try {
            $settings->putMany($values, (int) $user->id);
        } finally {
            $settings->forgetCache();
        }
        $audit->log('settings.smtp.tested', 'E-mail (SMTP)', null, null, null, [
            'result' => $result,
            'switched_off' => $switchedOff,
            'target_sha256' => SmtpMailSettingsService::targetFingerprint($stored['host'], $stored['port'], $stored['username']),
        ], user: $user, level: $result === 'ok' ? 'info' : 'warning');
        $runtime->apply();

        if ($result === 'ok') {
            $message = SmtpMailSettingsService::label('ok').' Proverite sanduče '.$user->email.' (i folder za neželjenu poštu).';
            if (!$stored['enabled']) {
                $message .= ' Sada možete da uključite prekidač „Koristi SMTP podešavanja iz baze“.';
            }

            return $redirect->with('status', $message);
        }

        $message = 'Probni e-mail nije poslat: '.SmtpMailSettingsService::label($result);
        if ($switchedOff) {
            $message .= ' Prekidač je isključen i slanje koristi .env podešavanja.';
        }

        return $redirect->withErrors(['smtp_test' => $message]);
    }

    private function currentPasswordConfirmed(Request $request): bool
    {
        $given = $request->input('current_password');
        $hash = (string) ($request->user()?->password_hash ?? '');

        if (!is_string($given) || $given === '' || strlen($given) > 255 || $hash === '') {
            return false;
        }
        try {
            return Hash::check($given, $hash);
        } catch (\Throwable) {
            return false;
        }
    }

    /** @param array<string,list<string>|string> $errors */
    private function backWithErrors(Request $request, array $errors): RedirectResponse
    {
        // Neither password is ever flashed back as old input.
        return redirect()->route('admin.settings.smtp.index')
            ->withErrors($errors)
            ->withInput($request->except(['smtp_password', 'current_password', '_token', '_method']));
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()?->hasRole('superadmin') === true, 403);
    }
}
