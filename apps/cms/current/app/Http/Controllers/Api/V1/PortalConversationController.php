<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PortalConversation;
use App\Models\PortalMessage;
use App\Models\User;
use App\Services\ModuleVisibilityService;
use App\Services\PortalConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class PortalConversationController extends Controller
{
    public function index(Request $request, ModuleVisibilityService $modules): JsonResponse
    {
        $this->ensureEnabled($modules);
        $user = $request->user();

        $conversations = PortalConversation::query()
            ->where('user_id', $user->id)
            ->with(['order:id,order_number', 'latestPublicMessage.sender:id,first_name,last_name,username'])
            ->withCount(['publicMessages as unread_count' => static fn ($messages) => $messages
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_by_customer_at')])
            ->latest('last_message_at')
            ->limit(100)
            ->get()
            ->map(fn (PortalConversation $conversation): array => $this->summary($conversation));

        $orders = Order::query()
            ->operational()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(100)
            ->get(['id', 'order_number', 'status', 'subtotal_rsd'])
            ->map(static fn (Order $order): array => [
                'id' => (int) $order->id,
                'order_number' => (string) $order->order_number,
                'status' => (string) $order->status,
                'subtotal_rsd' => (float) $order->subtotal_rsd,
            ]);

        return response()->json([
            'data' => $conversations,
            'orders' => $orders,
            'status_labels' => PortalConversation::statusLabels(),
        ]);
    }

    public function store(
        Request $request,
        PortalConversationService $conversations,
        ModuleVisibilityService $modules,
    ): JsonResponse {
        $this->ensureEnabled($modules);
        $user = $request->user();
        $data = $request->validate([
            'subject' => ['required', 'string', 'min:3', 'max:190'],
            'body' => ['required', 'string', 'min:2', 'max:10000'],
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')],
        ]);

        $order = null;
        if (!empty($data['order_id'])) {
            $order = Order::query()
                ->operational()
                ->where('user_id', $user->id)
                ->find((int) $data['order_id']);
            if ($order === null) {
                throw ValidationException::withMessages([
                    'order_id' => ['Izabrana porudžbina nije dostupna ovom korisniku.'],
                ]);
            }
        }

        try {
            $conversation = $conversations->createForCustomer(
                $user,
                (string) $data['subject'],
                (string) $data['body'],
                $order,
            );
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['conversation' => [$exception->getMessage()]]);
        }

        return response()->json(['data' => $this->detailPayload($conversation, $user)], 201);
    }

    public function show(
        Request $request,
        PortalConversation $conversation,
        PortalConversationService $conversations,
        ModuleVisibilityService $modules,
    ): JsonResponse {
        $this->ensureEnabled($modules);
        $user = $request->user();
        abort_unless((int) $conversation->user_id === (int) $user->id, 404);

        $conversations->markReadByCustomer($conversation, $user);
        $conversation->load([
            'order:id,order_number,status',
            'publicMessages.sender:id,first_name,last_name,username',
        ]);

        return response()->json(['data' => $this->detailPayload($conversation, $user)]);
    }

    public function reply(
        Request $request,
        PortalConversation $conversation,
        PortalConversationService $conversations,
        ModuleVisibilityService $modules,
    ): JsonResponse {
        $this->ensureEnabled($modules);
        $user = $request->user();
        abort_unless((int) $conversation->user_id === (int) $user->id, 404);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:10000']]);

        try {
            $conversations->customerReply($conversation, $user, (string) $data['body']);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['body' => [$exception->getMessage()]]);
        }

        $conversation = $conversation->fresh([
            'order:id,order_number,status',
            'publicMessages.sender:id,first_name,last_name,username',
        ]);

        return response()->json(['data' => $this->detailPayload($conversation, $user)]);
    }

    private function summary(PortalConversation $conversation): array
    {
        return [
            'id' => (int) $conversation->id,
            'subject' => (string) $conversation->subject,
            'status' => (string) $conversation->status,
            'status_label' => PortalConversation::statusLabels()[$conversation->status] ?? $conversation->status,
            'priority' => (string) $conversation->priority,
            'order' => $conversation->order ? [
                'id' => (int) $conversation->order->id,
                'order_number' => (string) $conversation->order->order_number,
            ] : null,
            'unread_count' => (int) ($conversation->unread_count ?? 0),
            'latest_message' => $conversation->latestPublicMessage ? $this->message($conversation->latestPublicMessage) : null,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'created_at' => $conversation->created_at?->toIso8601String(),
        ];
    }

    private function detailPayload(PortalConversation $conversation, User $user): array
    {
        return [
            'id' => (int) $conversation->id,
            'subject' => (string) $conversation->subject,
            'status' => (string) $conversation->status,
            'status_label' => PortalConversation::statusLabels()[$conversation->status] ?? $conversation->status,
            'priority' => (string) $conversation->priority,
            'order' => $conversation->order ? [
                'id' => (int) $conversation->order->id,
                'order_number' => (string) $conversation->order->order_number,
                'status' => (string) $conversation->order->status,
            ] : null,
            'messages' => $conversation->publicMessages
                ->map(fn (PortalMessage $message): array => $this->message($message, $user))
                ->values()
                ->all(),
            'can_reply' => !$conversation->isClosed(),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            'created_at' => $conversation->created_at?->toIso8601String(),
        ];
    }

    private function message(PortalMessage $message, ?User $viewer = null): array
    {
        return [
            'id' => (int) $message->id,
            'body' => (string) $message->body,
            'visibility' => 'public',
            'sender' => [
                'id' => (int) $message->sender_id,
                'name' => $message->sender?->displayName() ?? 'Korisnik',
                'is_me' => $viewer !== null && (int) $message->sender_id === (int) $viewer->id,
            ],
            'sent_at' => $message->sent_at?->toIso8601String(),
        ];
    }

    private function ensureEnabled(ModuleVisibilityService $modules): void
    {
        abort_unless($modules->enabled('customer_portal'), 404);
    }
}
