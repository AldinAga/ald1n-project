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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * SuperAdmin page "E-mail (SMTP)". The password is write-only: it is never
 * rendered, flashed, logged or written to the audit log.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SmtpSettingsController extends Controller
{
    private const CONNECTION_FIELDS = ['host', 'port', 'encryption', 'username', 'password'];

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
        $enabled = $request->boolean('smtp_enabled');

        $validator = Validator::make($request->all(), [
            'smtp_host' => ['nullable', 'string', 'max:253'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['nullable', 'string', Rule::in(SmtpMailSettingsService::ENCRYPTIONS)],
            'smtp_username' => ['nullable', 'string', 'max:254'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_timeout' => ['nullable', 'integer', 'between:1,60'],
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
            'smtp_timeout.integer' => 'Vreme čekanja mora biti ceo broj sekundi od 1 do 60.',
            'smtp_timeout.between' => 'Vreme čekanja mora biti ceo broj sekundi od 1 do 60.',
        ]);

        $validator->after(function ($validator) use ($request, $enabled, $before): void {
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
                if ($username !== '' && $password === '' && !$before['password_readable']) {
                    $errors->add('smtp_password', 'Unesite lozinku za navedeno korisničko ime.');
                }
            }
            if ($username !== '' && preg_match('/[\x00-\x20\x7f]/', $username) === 1 && !$errors->has('smtp_username')) {
                $errors->add('smtp_username', 'Korisničko ime ne sme sadržati razmake ni kontrolne znakove.');
            }
            if ($password !== '' && preg_match('/[\x00-\x1f\x7f]/', $password) === 1 && !$errors->has('smtp_password')) {
                $errors->add('smtp_password', 'Lozinka ne sme sadržati kontrolne znakove ni prelom reda.');
            }
        });

        if ($validator->fails()) {
            // The password is never flashed back as old input.
            return redirect()->route('admin.settings.smtp.index')
                ->withErrors($validator)
                ->withInput($request->except(['smtp_password', '_token', '_method']));
        }

        $host = mb_strtolower(trim((string) $request->input('smtp_host', '')));
        $port = trim((string) $request->input('smtp_port', ''));
        $timeout = trim((string) $request->input('smtp_timeout', ''));
        $username = trim((string) $request->input('smtp_username', ''));
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

        $values = [
            'smtp_enabled' => $enabled ? '1' : '0',
            'smtp_host' => $host,
            'smtp_port' => $after['port'] === null ? '' : (string) $after['port'],
            'smtp_encryption' => $after['encryption'],
            'smtp_username' => $username,
            'smtp_timeout' => $after['timeout'] === null ? '' : (string) $after['timeout'],
        ];
        if (array_intersect($changed, self::CONNECTION_FIELDS) !== []) {
            // An older test result no longer describes these settings.
            $values += ['smtp_last_test_at' => '', 'smtp_last_test_result' => '', 'smtp_last_test_by' => ''];
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
            'enabled' => $enabled,
            'target_sha256' => SmtpMailSettingsService::targetFingerprint($host, $after['port'], $username),
        ], user: $request->user());

        $source = $runtime->apply();
        $message = 'SMTP podešavanja su sačuvana.';
        if ($enabled && $source !== 'database') {
            $message .= ' Podešavanja nisu potpuna, pa se i dalje koristi slanje iz .env fajla.';
        } elseif ($source === 'database') {
            $message .= ' Slanje sada koristi SMTP server iz baze.';
        } else {
            $message .= ' Slanje i dalje koristi .env podešavanja.';
        }

        return redirect()->route('admin.settings.smtp.index')->with('status', $message);
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
            $settings->putMany([
                'smtp_password' => '',
                'smtp_last_test_at' => '',
                'smtp_last_test_result' => '',
                'smtp_last_test_by' => '',
            ], (int) $request->user()->getAuthIdentifier());
        } finally {
            $settings->forgetCache();
        }
        $audit->log('settings.smtp.password_cleared', 'E-mail (SMTP)', null, null, null, [
            'had_stored_value' => $before['password_stored'],
            'target_sha256' => SmtpMailSettingsService::targetFingerprint($before['host'], $before['port'], $before['username']),
        ], user: $request->user());

        $source = $runtime->apply();
        $message = 'Lozinka je obrisana.';
        if ($before['enabled'] && $source !== 'database') {
            $message .= ' Slanje se vratilo na .env podešavanja dok ne unesete novu lozinku.';
        }

        return redirect()->route('admin.settings.smtp.index')->with('status', $message);
    }

    public function test(
        Request $request,
        SettingsService $settings,
        SmtpMailSettingsService $smtp,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorizeOwner($request);
        /** @var User $user */
        $user = $request->user();
        $stored = $smtp->stored();
        $result = $smtp->sendTest($user);

        try {
            $settings->putMany([
                'smtp_last_test_at' => now()->toISOString(),
                'smtp_last_test_result' => $result,
                'smtp_last_test_by' => (string) $user->id,
            ], (int) $user->id);
        } finally {
            $settings->forgetCache();
        }
        $audit->log('settings.smtp.tested', 'E-mail (SMTP)', null, null, null, [
            'result' => $result,
            'target_sha256' => SmtpMailSettingsService::targetFingerprint($stored['host'], $stored['port'], $stored['username']),
        ], user: $user, level: $result === 'ok' ? 'info' : 'warning');

        $redirect = redirect()->route('admin.settings.smtp.index');
        if ($result === 'ok') {
            return $redirect->with('status', SmtpMailSettingsService::label('ok').' Proverite sanduče '.$user->email.' (i folder za neželjenu poštu).');
        }

        return $redirect->withErrors(['smtp_test' => 'Probni e-mail nije poslat: '.SmtpMailSettingsService::label($result)]);
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()?->hasRole('superadmin') === true, 403);
    }
}
