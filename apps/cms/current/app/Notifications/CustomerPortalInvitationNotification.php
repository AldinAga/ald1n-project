<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class CustomerPortalInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly int $expiresInHours,
    ) {
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('customer-activation.show', ['token' => $this->token]);
        $mobileUrl = 'ald1n://activate-account?token='.rawurlencode($this->token);
        $name = method_exists($notifiable, 'displayName') ? $notifiable->displayName() : 'korisniče';

        return (new MailMessage())
            ->subject('Aktivirajte svoj korisnički portal')
            ->greeting('Zdravo, '.$name)
            ->line('Za vas je pripremljen bezbedan pristup korisničkom portalu.')
            ->line('Na portalu možete pratiti porudžbine, dokumente, uplate, garancije, reklamacije, servisne termine i poruke.')
            ->action('Aktiviraj nalog', $url)
            ->line('[Otvori aktivaciju u Ald1n CMS mobilnoj aplikaciji]('.$mobileUrl.')')
            ->line('Link važi '.$this->expiresInHours.' sati, može se iskoristiti samo jednom i postaje nevažeći nakon aktivacije.')
            ->line('Ako ne očekujete ovaj poziv, ignorišite poruku ili kontaktirajte administratora.');
    }
}
