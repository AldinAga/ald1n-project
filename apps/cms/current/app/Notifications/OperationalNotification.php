<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OperationalNotification extends Notification
{
    use Queueable;

    /** @param array<string,mixed> $data */
    public function __construct(private readonly array $data) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        $channels = [];
        if (($this->data['_in_app'] ?? true) !== false) {
            $channels[] = 'database';
        }
        if ((bool) config('services.operational_notifications.mail_enabled', false)
            && ($this->data['_email'] ?? false) === true
            && filled($notifiable->routeNotificationFor('mail', $this))) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /** @return array<string,mixed> */
    public function toArray(object $notifiable): array
    {
        $data = $this->data;
        unset($data['_in_app'], $data['_email'], $data['_push']);
        return $data;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject((string) ($this->data['title'] ?? 'Ald1n CMS obaveštenje'))
            ->greeting('Pozdrav,')
            ->line((string) ($this->data['message'] ?? 'Imate novo obaveštenje u Ald1n CMS-u.'));

        $url = trim((string) ($this->data['url'] ?? ''));
        if ($url !== '') {
            $mail->action((string) ($this->data['action_label'] ?? 'Otvori u CMS-u'), $url);
        }

        return $mail->line('Ovo je automatsko poslovno obaveštenje.');
    }
}
