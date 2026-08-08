<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly int $expiresInMinutes,
    ) {
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token]);
        $name = method_exists($notifiable, 'displayName')
            ? $notifiable->displayName()
            : 'korisniče';

        return (new MailMessage)
            ->subject('Resetovanje lozinke - Ald1n CMS')
            ->greeting('Zdravo, '.$name)
            ->line('Primili smo zahtev za resetovanje lozinke vašeg Ald1n CMS naloga.')
            ->action('Postavi novu lozinku', $url)
            ->line('Link važi '.$this->expiresInMinutes.' minuta i može se iskoristiti samo jednom.')
            ->line('Promenom lozinke biće opozvane aktivne API sesije i trajna prijava na drugim uređajima.')
            ->line('Ako niste poslali ovaj zahtev, ignorišite ovu poruku.');
    }
}
