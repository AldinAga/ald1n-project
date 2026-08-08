<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendTestMailCommand extends Command
{
    protected $signature = 'app:send-test-mail {email : Adresa primaoca}';
    protected $description = 'Pošalji test poruku radi provere SMTP konfiguracije';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('E-mail adresa nije validna.');
            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');
        if (in_array($mailer, ['log', 'array'], true)) {
            $this->error('MAIL_MAILER mora biti smtp ili sendmail za stvarno slanje poruka.');
            return self::FAILURE;
        }

        try {
            Mail::raw(
                'SMTP konfiguracija za Ald1n CMS Laravel v'.config('app.version').' radi ispravno.',
                static function ($message) use ($email): void {
                    $message->to($email)->subject('Ald1n CMS - test e-mail');
                }
            );
        } catch (Throwable $exception) {
            $this->error('Test poruka nije poslata: '.$exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Test poruka je poslata na '.$email.'.');
        return self::SUCCESS;
    }
}
