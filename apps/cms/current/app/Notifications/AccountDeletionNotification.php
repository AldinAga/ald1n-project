<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail poruke u postupku brisanja naloga: potvrda zahteva, početak roka
 * mirovanja (sa linkom za poništavanje) i obaveštenje da brisanje čeka
 * zatvaranje obaveza. Linkovi se grade iz APP_URL, nikad iz Host zaglavlja.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class AccountDeletionNotification extends Notification
{
    use Queueable;

    public const CONFIRM = 'confirm';
    public const SCHEDULED = 'scheduled';
    public const WAITING = 'waiting';

    public function __construct(
        public readonly string $kind,
        public readonly ?string $token = null,
        public readonly int $graceDays = 14,
    ) {
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $base = rtrim((string) config('app.url'), '/');
        $name = method_exists($notifiable, 'displayName') ? $notifiable->displayName() : 'korisniče';
        $mail = (new MailMessage)->greeting('Zdravo, '.$name);

        if ($this->kind === self::CONFIRM) {
            return $mail->subject('Potvrda brisanja naloga')
                ->line('Primili smo zahtev za brisanje vašeg naloga.')
                ->action('Potvrdi brisanje naloga', $base.route('account-deletion.confirm.show', ['token' => (string) $this->token], false))
                ->line('Link važi 60 minuta. Posle potvrde nalog se odmah blokira, a konačno brisanje sledi posle '.$this->graceDays.' dana. U tom roku zahtev možete poništiti.')
                ->line('Ako zahtev niste poslali vi, ignorišite ovu poruku. Ništa se neće desiti.');
        }

        if ($this->kind === self::SCHEDULED) {
            return $mail->subject('Brisanje naloga je zakazano')
                ->line('Vaš nalog je blokiran, a konačno brisanje je zakazano za '.$this->graceDays.' dana.')
                ->line('Ako ste se predomislili, poništite zahtev dok rok traje.')
                ->action('Poništi brisanje naloga', $base.route('account-deletion.cancel.show', ['token' => (string) $this->token], false))
                ->line('Posle isteka roka brisanje se ne može poništiti.');
        }

        return $mail->subject('Brisanje naloga čeka zatvaranje obaveza')
            ->line('Rok za brisanje vašeg naloga je istekao, ali nalog ima otvorene provizije ili zaduženja.')
            ->line('Brisanje se nastavlja automatski čim se obaveze zatvore. Za pitanja nas kontaktirajte.')
            ->line('Zahtev možete poništiti linkom iz prethodne poruke.');
    }
}
