<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalConversation;
use App\Models\PortalMessage;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ModuleVisibilityService;
use App\Services\PortalConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class PortalConversationController extends Controller
{
    public function show(
        Request $request,
        PortalConversation $conversation,
        PortalConversationService $conversations,
        ModuleVisibilityService $modules,
    ): JsonResponse {
        $this->ensureEnabled($modules);
        $conversations->markReadByStaff($conversation);
        return response()->json(['data' => $this->payload($conversation)]);
    }

    public function reply(
        Request $request,
        PortalConversation $conversation,
        PortalConversationService $conversations,
        AuditLogger $audit,
        ModuleVisibilityService $modules,
    ): JsonResponse {
        $this->ensureEnabled($modules);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:10000'],
            'visibility' => ['required', Rule::in(['public', 'internal'])],
            'status' => ['nullable', Rule::in(['open', 'waiting_staff', 'waiting_customer', 'closed'])],
        ]);

        $conversations->staffReply(
            $conversation,
            $request->user(),
            (string) $data['body'],
            (string) $data['visibility'],
            $data['status'] ?? null,
        );

        $audit->log(
            'customer_portal.conversation_replied_api',
            'Odgovoreno na portal komunikaciju kroz mobilni Admin API',
            $conversation,
            metadata: ['visibility' => $data['visibility'], 'status' => $data['status'] ?? null],
            user: $request->user(),
            request: $request,
        );

        return response()->json(['data' => $this->payload($conversation)]);
    }

    public function update(
        Request $request,
        PortalConversation $conversation,
        PortalConversationService $conversations,
        AuditLogger $audit,
        ModuleVisibilityService $modules,
    ): JsonResponse {
        $this->ensureEnabled($modules);
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'waiting_staff', 'waiting_customer', 'closed'])],
            'priority' => ['required', Rule::in(['normal', 'high'])],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(static fn ($query) => $query->where('status', 'active')),
            ],
        ]);

        if (!empty($data['assigned_to'])) {
            $staff = User::query()->findOrFail((int) $data['assigned_to']);
            abort_unless($staff->hasRole('admin', 'superadmin'), 422);
        }

        $before = $conversation->only(['status', 'priority', 'assigned_to']);
        $conversation->forceFill([
            'priority' => (string) $data['priority'],
            'assigned_to' => $data['assigned_to'] ?? null,
        ])->save();
        $conversation = $conversations->setStatus($conversation, $request->user(), (string) $data['status']);

        $audit->log(
            'customer_portal.conversation_updated_api',
            'Ažurirana portal komunikacija kroz mobilni Admin API',
            $conversation,
            $before,
            $conversation->only(['status', 'priority', 'assigned_to']),
            user: $request->user(),
            request: $request,
        );

        return response()->json(['data' => $this->payload($conversation)]);
    }

    private function payload(PortalConversation $conversation): array
    {
        $conversation->load([
            'customer:id,first_name,last_name,username,email',
            'order:id,order_number,status',
            'assignee:id,first_name,last_name,username',
            'messages.sender:id,first_name,last_name,username',
        ]);

        $staff = User::query()
            ->where('status', 'active')
            ->whereHas('role', static fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))
            ->with('role:id,name,slug')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('username')
            ->get()
            ->map(static fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => $user->displayName(),
                'role' => $user->role?->slug,
            ]);

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
                'status' => (string) $conversation->order->status,
            ] : null,
            'assignee' => $conversation->assignee ? [
                'id' => (int) $conversation->assignee->id,
                'name' => $conversation->assignee->displayName(),
            ] : null,
            'messages' => $conversation->messages->map(static fn (PortalMessage $message): array => [
                'id' => (int) $message->id,
                'body' => (string) $message->body,
                'visibility' => (string) $message->visibility,
                'sender' => [
                    'id' => (int) $message->sender_id,
                    'name' => $message->sender?->displayName() ?? 'Korisnik',
                ],
                'sent_at' => $message->sent_at?->toIso8601String(),
            ])->values()->all(),
            'staff_options' => $staff,
            'status_labels' => PortalConversation::statusLabels(),
            'priority_labels' => ['normal' => 'Normalan', 'high' => 'Visok'],
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
        ];
    }

    private function ensureEnabled(ModuleVisibilityService $modules): void
    {
        abort_unless($modules->enabled('customer_portal'), 404);
    }
}
