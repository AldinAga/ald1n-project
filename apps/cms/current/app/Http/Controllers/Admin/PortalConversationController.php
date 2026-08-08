<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalConversation;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PortalConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

final class PortalConversationController extends Controller
{
    public function show(
        Request $request,
        PortalConversation $conversation,
        PortalConversationService $service,
    ): View {
        $service->markReadByStaff($conversation);
        $conversation->load([
            'customer:id,first_name,last_name,username,email,phone',
            'order:id,order_number,status',
            'assignee:id,first_name,last_name,username',
            'messages.sender:id,first_name,last_name,username,role_id',
            'messages.sender.role:id,name,slug',
        ]);

        return view('admin.customer-portal.conversation', [
            'conversation' => $conversation,
            'statusLabels' => PortalConversation::statusLabels(),
            'staffUsers' => User::query()->where('status', 'active')->whereHas('role', static fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))->orderBy('first_name')->orderBy('username')->get(),
        ]);
    }

    public function reply(
        Request $request,
        PortalConversation $conversation,
        PortalConversationService $service,
        AuditLogger $audit,
    ): RedirectResponse {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:10000'],
            'visibility' => ['required', Rule::in(['public', 'internal'])],
            'status' => ['nullable', Rule::in(['open', 'waiting_staff', 'waiting_customer', 'closed'])],
        ]);

        try {
            $message = $service->staffReply(
                $conversation,
                $request->user(),
                (string) $data['body'],
                (string) $data['visibility'],
                $data['status'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['body' => $exception->getMessage()]);
        }

        $audit->log(
            'customer_portal.staff_message_sent',
            'Odgovor u komunikaciji sa kupcem',
            $message,
            metadata: ['conversation_id' => $conversation->id, 'visibility' => $data['visibility']],
        );

        return back()->with('status', $data['visibility'] === 'internal' ? 'Interna beleška je sačuvana.' : 'Odgovor je poslat kupcu.');
    }

    public function update(
        Request $request,
        PortalConversation $conversation,
        PortalConversationService $service,
        AuditLogger $audit,
    ): RedirectResponse {
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'waiting_staff', 'waiting_customer', 'closed'])],
            'priority' => ['required', Rule::in(['normal', 'high'])],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        if (!empty($data['assigned_to'])) {
            $validAssignee = User::query()
                ->whereKey((int) $data['assigned_to'])
                ->where('status', 'active')
                ->whereHas('role', static fn ($roles) => $roles->whereIn('slug', ['admin', 'superadmin']))
                ->exists();
            if (!$validAssignee) {
                return back()->withErrors(['assigned_to' => 'Izabrana osoba nema aktivan administratorski nalog.']);
            }
        }

        $before = $conversation->toArray();
        $conversation->forceFill([
            'priority' => $data['priority'],
            'assigned_to' => $data['assigned_to'] ?: null,
        ])->save();
        $conversation = $service->setStatus($conversation, $request->user(), (string) $data['status']);
        $audit->log('customer_portal.conversation_updated', 'Ažurirana komunikacija sa kupcem', $conversation, $before, $conversation->toArray());

        return back()->with('status', 'Komunikacija je ažurirana.');
    }
}
