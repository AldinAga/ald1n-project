<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PortalConversation;
use App\Models\PortalMessage;
use App\Models\User;
use App\Services\CustomerPortalAdminService;
use App\Services\ModuleVisibilityService;
use App\Services\PortalSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

final class CustomerPortalController extends Controller
{
    public function index(Request $request, ModuleVisibilityService $modules): JsonResponse
    {
        $this->ensureEnabled($modules);
        $query = User::query()
            ->whereHas('role', static fn ($roles) => $roles->where('slug', 'user'))
            ->with(['role', 'group', 'latestActivationToken'])
            ->withCount(['orders', 'portalConversations'])
            ->latest('id');

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(static function ($users) use ($search): void {
                $users->where('username', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%');
            });
        }

        $status = (string) $request->query('status', '');
        if (in_array($status, ['pending', 'active', 'blocked'], true)) {
            $query->where('status', $status);
        }

        $users = $query->limit(100)->get()->map(fn (User $user): array => $this->userSummary($user));
        $conversationAvailable = Schema::hasTable('portal_conversations') && Schema::hasTable('portal_messages');
        $conversations = $conversationAvailable
            ? PortalConversation::query()
                ->with([
                    'customer:id,first_name,last_name,username,email',
                    'order:id,order_number',
                    'assignee:id,first_name,last_name,username',
                    'latestPublicMessage.sender:id,first_name,last_name,username',
                ])
                ->withCount(['publicMessages as unread_staff_count' => static fn ($messages) => $messages->whereNull('read_by_staff_at')])
                ->latest('last_message_at')
                ->limit(20)
                ->get()
                ->map(fn (PortalConversation $conversation): array => $this->conversationSummary($conversation))
            : collect();

        return response()->json([
            'data' => [
                'users' => $users,
                'conversations' => $conversations,
                'stats' => [
                    'pending_users' => User::query()->where('status', 'pending')->whereHas('role', static fn ($r) => $r->where('slug', 'user'))->count(),
                    'active_users' => User::query()->where('status', 'active')->whereHas('role', static fn ($r) => $r->where('slug', 'user'))->count(),
                    'open_conversations' => $conversationAvailable ? PortalConversation::query()->where('status', '!=', 'closed')->count() : 0,
                    'unread_messages' => $conversationAvailable ? PortalMessage::query()->where('visibility', 'public')->whereNull('read_by_staff_at')->count() : 0,
                ],
                'status_labels' => PortalConversation::statusLabels(),
            ],
        ]);
    }

    public function store(
        Request $request,
        CustomerPortalAdminService $portal,
        ModuleVisibilityService $modules,
    ): JsonResponse {
        $this->ensureEnabled($modules);
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ]);

        $result = $portal->createCustomer($data, $request->user());
        $user = $result['user']->loadMissing(['role', 'group', 'latestActivationToken']);

        return response()->json([
            'message' => $result['invitation_sent']
                ? 'Kupac je kreiran i aktivacioni poziv je poslat.'
                : 'Kupac je kreiran, ali aktivacioni poziv nije poslat.',
            'data' => $this->userSummary($user),
            'invitation_sent' => $result['invitation_sent'],
            'invitation_error' => $result['invitation_error'],
        ], 201);
    }

    public function show(
        Request $request,
        User $user,
        PortalSessionService $sessions,
        ModuleVisibilityService $modules,
    ): JsonResponse {
        $this->ensureEnabled($modules);
        abort_unless($user->hasRole('user'), 404);
        $user->load(['role', 'group', 'latestActivationToken']);

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (Order $order): array => $this->orderPayload($order));

        $search = trim((string) $request->query('order_q', ''));
        $orderSearch = collect();
        if ($search !== '') {
            $orderSearch = Order::query()
                ->with('user:id,first_name,last_name,username,email')
                ->where(static function ($orders) use ($search): void {
                    $orders->where('order_number', 'like', '%'.$search.'%')
                        ->orWhere('shipping_full_name', 'like', '%'.$search.'%')
                        ->orWhere('shipping_phone', 'like', '%'.$search.'%');
                })
                ->latest('id')
                ->limit(30)
                ->get()
                ->map(fn (Order $order): array => $this->orderPayload($order, true));
        }

        $activeSessions = $sessions->activeReadOnlyFor($user)->map(static fn ($session): array => [
            'id' => (int) $session->id,
            'device_label' => (string) ($session->device_label ?: 'Web pregledač'),
            'ip_address' => $session->ip_address,
            'remembered' => (bool) $session->remembered,
            'logged_in_at' => $session->logged_in_at?->toIso8601String(),
            'last_seen_at' => $session->last_seen_at?->toIso8601String(),
        ]);

        $conversations = Schema::hasTable('portal_conversations')
            ? PortalConversation::query()
                ->where('user_id', $user->id)
                ->with(['order:id,order_number', 'latestPublicMessage.sender:id,first_name,last_name,username'])
                ->latest('last_message_at')
                ->limit(20)
                ->get()
                ->map(fn (PortalConversation $conversation): array => $this->conversationSummary($conversation))
            : collect();

        return response()->json([
            'data' => [
                'customer' => $this->userSummary($user),
                'orders' => $orders,
                'order_search' => $orderSearch,
                'active_web_sessions' => $activeSessions,
                'conversations' => $conversations,
                'status_labels' => PortalConversation::statusLabels(),
            ],
        ]);
    }

    public function invite(
        Request $request,
        User $user,
        CustomerPortalAdminService $portal,
        ModuleVisibilityService $modules,
    ): JsonResponse {
        $this->ensureEnabled($modules);
        abort_unless($user->hasRole('user'), 404);
        $token = $portal->invite($user, $request->user());

        return response()->json([
            'message' => 'Aktivacioni poziv je poslat.',
            'data' => ['expires_at' => $token->expires_at?->toIso8601String()],
        ]);
    }

    public function linkOrder(
        Request $request,
        User $user,
        CustomerPortalAdminService $portal,
        ModuleVisibilityService $modules,
    ): JsonResponse {
        $this->ensureEnabled($modules);
        abort_unless($user->hasRole('user'), 404);
        $data = $request->validate([
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'confirm_reassign' => ['nullable', 'boolean'],
            'move_related_portal_data' => ['nullable', 'boolean'],
        ]);

        $result = $portal->linkOrder(
            $user,
            (int) $data['order_id'],
            (string) $data['reason'],
            (bool) ($data['confirm_reassign'] ?? false),
            (bool) ($data['move_related_portal_data'] ?? false),
            $request->user(),
        );

        return response()->json([
            'message' => $result['changed']
                ? 'Porudžbina je povezana sa kupcem.'
                : 'Porudžbina je već povezana sa ovim kupcem.',
            'data' => [
                'changed' => $result['changed'],
                'order' => $this->orderPayload($result['order']),
                'warranties_updated' => $result['warranties_updated'],
                'conversations_updated' => $result['conversations_updated'],
            ],
        ]);
    }

    public function revokeSessions(
        Request $request,
        User $user,
        CustomerPortalAdminService $portal,
        ModuleVisibilityService $modules,
    ): JsonResponse {
        $this->ensureEnabled($modules);
        abort_unless($user->hasRole('user'), 404);
        $result = $portal->revokeSessions($user, $request->user());

        return response()->json([
            'message' => 'Sve aktivne prijave kupca su opozvane.',
            'data' => $result,
        ]);
    }

    private function userSummary(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => $user->displayName(),
            'username' => (string) $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => (string) $user->status,
            'group' => $user->group ? ['id' => (int) $user->group->id, 'name' => $user->group->name] : null,
            'orders_count' => (int) ($user->orders_count ?? $user->orders()->count()),
            'conversations_count' => (int) ($user->portal_conversations_count ?? $user->portalConversations()->count()),
            'activation' => $user->latestActivationToken ? [
                'expires_at' => $user->latestActivationToken->expires_at?->toIso8601String(),
                'used_at' => $user->latestActivationToken->used_at?->toIso8601String(),
                'valid' => $user->latestActivationToken->used_at === null
                    && (bool) $user->latestActivationToken->expires_at?->isFuture(),
            ] : null,
        ];
    }

    private function orderPayload(Order $order, bool $includeOwner = false): array
    {
        $payload = [
            'id' => (int) $order->id,
            'order_number' => (string) $order->order_number,
            'status' => (string) $order->status,
            'shipping_full_name' => $order->shipping_full_name,
            'shipping_phone' => $order->shipping_phone,
            'subtotal_rsd' => (float) $order->subtotal_rsd,
        ];
        if ($includeOwner) {
            $payload['owner'] = $order->user ? [
                'id' => (int) $order->user->id,
                'name' => $order->user->displayName(),
            ] : null;
        }
        return $payload;
    }

    private function conversationSummary(PortalConversation $conversation): array
    {
        return [
            'id' => (int) $conversation->id,
            'subject' => (string) $conversation->subject,
            'status' => (string) $conversation->status,
            'status_label' => PortalConversation::statusLabels()[$conversation->status] ?? $conversation->status,
            'priority' => (string) $conversation->priority,
            'customer' => $conversation->customer ? [
                'id' => (int) $conversation->customer->id,
                'name' => $conversation->customer->displayName(),
                'email' => $conversation->customer->email,
            ] : null,
            'order' => $conversation->order ? [
                'id' => (int) $conversation->order->id,
                'order_number' => (string) $conversation->order->order_number,
            ] : null,
            'assignee' => $conversation->assignee ? [
                'id' => (int) $conversation->assignee->id,
                'name' => $conversation->assignee->displayName(),
            ] : null,
            'unread_staff_count' => (int) ($conversation->unread_staff_count ?? 0),
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
        ];
    }

    private function ensureEnabled(ModuleVisibilityService $modules): void
    {
        abort_unless($modules->enabled('customer_portal'), 404);
    }
}
