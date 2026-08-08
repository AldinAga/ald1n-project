<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class MailDoctorCommand extends Command
{
    protected $signature = 'app:mail-doctor';
    protected $description = 'Proveri e-mail transport bez prikazivanja SMTP lozinke';

    public function handle(): int
    {
        $mailer = (string) config('mail.default');
        $failed = false;

        $this->line('MAIL_MAILER: '.$mailer);
        $this->line('Pošiljalac: '.(string) config('mail.from.address').' / '.(string) config('mail.from.name'));

        if (!in_array($mailer, ['smtp', 'sendmail'], true)) {
            $this->error('Za stvarno slanje postavi MAIL_MAILER=smtp ili MAIL_MAILER=sendmail.');
            return self::FAILURE;
        }

        if ($mailer === 'smtp') {
            $host = trim((string) config('mail.mailers.smtp.host'));
            $port = (int) config('mail.mailers.smtp.port');
            $username = trim((string) config('mail.mailers.smtp.username'));
            $password = (string) config('mail.mailers.smtp.password');
            $scheme = trim((string) config('mail.mailers.smtp.scheme'));

            $checks = [
                'SMTP host' => $host !== '',
                'SMTP port' => $port > 0,
                'SMTP korisnik' => $username !== '',
                'SMTP lozinka' => $password !== '',
                'Pošiljalac' => filter_var((string) config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false,
            ];

            $this->line('SMTP: '.($scheme !== '' ? $scheme.'://' : '').$host.':'.$port);
            foreach ($checks as $label => $ok) {
                $this->line(($ok ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' '.$label);
                $failed = $failed || !$ok;
            }
        } else {
            $path = (string) config('mail.mailers.sendmail.path');
            $binary = strtok($path, ' ') ?: '';
            $ok = $binary !== '' && is_executable($binary);
            $this->line(($ok ? '<fg=green>PASS</>' : '<fg=red>FAIL</>').' sendmail: '.$path);
            $failed = !$ok;
        }

        $this->newLine();
        $this->line('Posle ispravke pokreni: php artisan optimize:clear');
        $this->line('Zatim: php artisan app:send-test-mail tvoja-adresa@example.com');

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
