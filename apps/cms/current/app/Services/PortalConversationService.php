<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\PortalConversation;
use App\Models\PortalMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class PortalConversationService
{
    public function __construct(private readonly OperationalNotificationService $notifications)
    {
    }

    public function createForCustomer(User $customer, string $subject, string $body, ?Order $order = null): PortalConversation
    {
        $this->assertAvailable();
        if ($order !== null && $order->user_id !== $customer->id) {
            throw new RuntimeException('Izabrana porudžbina ne pripada korisniku.');
        }

        $conversation = DB::transaction(function () use ($customer, $subject, $body, $order): PortalConversation {
            $conversation = PortalConversation::query()->create([
                'user_id' => $customer->id,
                'order_id' => $order?->id,
                'assigned_to' => null,
                'created_by' => $customer->id,
                'subject' => trim($subject),
                'status' => 'waiting_staff',
                'priority' => 'normal',
                'last_message_at' => now(),
            ]);

            PortalMessage::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $customer->id,
                'visibility' => 'public',
                'body' => trim($body),
                'sent_at' => now(),
                'read_by_customer_at' => now(),
                'read_by_staff_at' => null,
                'created_at' => now(),
            ]);

            return $conversation;
        }, 3);

        $this->notifyStaff($conversation, 'Nova poruka kupca', $customer->displayName().' je otvorio novu temu: '.$conversation->subject);

        return $conversation->fresh(['customer', 'order', 'messages.sender']);
    }

    public function customerReply(PortalConversation $conversation, User $customer, string $body): PortalMessage
    {
        $this->assertCustomerOwns($conversation, $customer);
        if ($conversation->isClosed()) {
            throw new RuntimeException('Zatvorena tema ne može da primi novu poruku.');
        }

        $message = DB::transaction(function () use ($conversation, $customer, $body): PortalMessage {
            $message = PortalMessage::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $customer->id,
                'visibility' => 'public',
                'body' => trim($body),
                'sent_at' => now(),
                'read_by_customer_at' => now(),
                'read_by_staff_at' => null,
                'created_at' => now(),
            ]);
            $conversation->forceFill([
                'status' => 'waiting_staff',
                'last_message_at' => now(),
            ])->save();

            return $message;
        }, 3);

        $this->notifyStaff($conversation, 'Nova poruka kupca', $customer->displayName().' je odgovorio u temi: '.$conversation->subject);

        return $message;
    }

    public function staffReply(
        PortalConversation $conversation,
        User $staff,
        string $body,
        string $visibility = 'public',
        ?string $status = null,
    ): PortalMessage {
        $this->assertAvailable();
        $visibility = $visibility === 'internal' ? 'internal' : 'public';
        $nextStatus = $status;
        if (!in_array($nextStatus, ['open', 'waiting_staff', 'waiting_customer', 'closed'], true)) {
            $nextStatus = $visibility === 'public' ? 'waiting_customer' : $conversation->status;
        }

        $message = DB::transaction(function () use ($conversation, $staff, $body, $visibility, $nextStatus): PortalMessage {
            $message = PortalMessage::query()->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $staff->id,
                'visibility' => $visibility,
                'body' => trim($body),
                'sent_at' => now(),
                'read_by_customer_at' => $visibility === 'internal' ? now() : null,
                'read_by_staff_at' => now(),
                'created_at' => now(),
            ]);

            $values = [
                'assigned_to' => $conversation->assigned_to ?: $staff->id,
                'status' => $nextStatus,
                'last_message_at' => now(),
            ];
            if ($nextStatus === 'closed') {
                $values['closed_at'] = now();
                $values['closed_by'] = $staff->id;
            } else {
                $values['closed_at'] = null;
                $values['closed_by'] = null;
            }
            $conversation->forceFill($values)->save();

            return $message;
        }, 3);

        if ($visibility === 'public') {
            $this->notifications->send($conversation->customer, [
                'event' => 'portal_message.staff_reply',
                'title' => 'Nova poruka podrške',
                'message' => 'Odgovoreno je u temi: '.$conversation->subject,
                'url' => route('portal.messages.show', $conversation),
                'action_label' => 'Otvori poruku',
                'icon' => 'mail',
                'severity' => 'info',
                'portal_conversation_id' => $conversation->id,
            ]);
        }

        return $message;
    }

    public function setStatus(PortalConversation $conversation, User $staff, string $status): PortalConversation
    {
        if (!in_array($status, ['open', 'waiting_staff', 'waiting_customer', 'closed'], true)) {
            throw new RuntimeException('Nepoznat status komunikacije.');
        }

        $values = [
            'status' => $status,
            'assigned_to' => $conversation->assigned_to ?: $staff->id,
        ];
        if ($status === 'closed') {
            $values['closed_at'] = now();
            $values['closed_by'] = $staff->id;
        } else {
            $values['closed_at'] = null;
            $values['closed_by'] = null;
        }
        $conversation->forceFill($values)->save();

        return $conversation->fresh();
    }

    public function markReadByCustomer(PortalConversation $conversation, User $customer): int
    {
        $this->assertCustomerOwns($conversation, $customer);

        return PortalMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('visibility', 'public')
            ->where('sender_id', '!=', $customer->id)
            ->whereNull('read_by_customer_at')
            ->update(['read_by_customer_at' => now()]);
    }

    public function markReadByStaff(PortalConversation $conversation): int
    {
        return PortalMessage::query()
            ->where('conversation_id', $conversation->id)
            ->where('visibility', 'public')
            ->where('sender_id', $conversation->user_id)
            ->whereNull('read_by_staff_at')
            ->update(['read_by_staff_at' => now()]);
    }

    private function notifyStaff(PortalConversation $conversation, string $title, string $message): void
    {
        User::query()
            ->where('status', 'active')
            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['admin', 'superadmin']))
            ->orderBy('id')
            ->each(function (User $recipient) use ($conversation, $title, $message): void {
                $this->notifications->send($recipient, [
                    'event' => 'portal_message.customer_reply',
                    'title' => $title,
                    'message' => $message,
                    'url' => route('admin.customer-portal.conversations.show', $conversation),
                    'action_label' => 'Otvori komunikaciju',
                    'icon' => 'mail',
                    'severity' => 'info',
                    'portal_conversation_id' => $conversation->id,
                ]);
            });
    }

    private function assertCustomerOwns(PortalConversation $conversation, User $customer): void
    {
        $this->assertAvailable();
        if ($conversation->user_id !== $customer->id) {
            throw new RuntimeException('Komunikacija ne pripada prijavljenom korisniku.');
        }
    }

    private function assertAvailable(): void
    {
        if (!Schema::hasTable('portal_conversations') || !Schema::hasTable('portal_messages')) {
            throw new RuntimeException('Centar za komunikaciju nije spreman. Pokrenite migracije.');
        }
    }
}
