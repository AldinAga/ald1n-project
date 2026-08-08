<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PortalConversation;
use App\Services\PortalConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;

final class PortalConversationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $available = Schema::hasTable('portal_conversations') && Schema::hasTable('portal_messages');
        $conversations = $available
            ? PortalConversation::query()
                ->where('user_id', $user->id)
                ->with(['order:id,order_number', 'assignee:id,first_name,last_name,username', 'latestPublicMessage.sender:id,first_name,last_name,username'])
                ->withCount(['publicMessages as unread_count' => static fn ($query) => $query
                    ->where('sender_id', '!=', $user->id)
                    ->whereNull('read_by_customer_at')])
                ->latest('last_message_at')
                ->latest('id')
                ->paginate(20)
            : null;

        return view('portal.messages.index', [
            'conversations' => $conversations,
            'orders' => Order::query()->where('user_id', $user->id)->latest('id')->limit(100)->get(['id', 'order_number']),
            'available' => $available,
            'statusLabels' => PortalConversation::statusLabels(),
        ]);
    }

    public function store(Request $request, PortalConversationService $service): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'subject' => ['required', 'string', 'min:3', 'max:190'],
            'body' => ['required', 'string', 'min:2', 'max:10000'],
            'order_id' => ['nullable', 'integer'],
        ]);

        $order = null;
        if (!empty($data['order_id'])) {
            $order = Order::query()->where('user_id', $user->id)->findOrFail((int) $data['order_id']);
        }

        try {
            $conversation = $service->createForCustomer(
                $user,
                (string) $data['subject'],
                (string) $data['body'],
                $order,
            );
        } catch (RuntimeException $exception) {
            return back()->withErrors(['body' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('portal.messages.show', $conversation)
            ->with('status', 'Poruka je poslata korisničkoj podršci.');
    }

    public function show(Request $request, PortalConversation $conversation, PortalConversationService $service): View
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);
        $service->markReadByCustomer($conversation, $request->user());
        $conversation->load([
            'order:id,order_number',
            'assignee:id,first_name,last_name,username',
            'publicMessages.sender:id,first_name,last_name,username,role_id',
            'publicMessages.sender.role:id,name,slug',
        ]);

        return view('portal.messages.show', [
            'conversation' => $conversation,
            'statusLabels' => PortalConversation::statusLabels(),
        ]);
    }

    public function reply(Request $request, PortalConversation $conversation, PortalConversationService $service): RedirectResponse
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);
        $data = $request->validate(['body' => ['required', 'string', 'min:2', 'max:10000']]);

        try {
            $service->customerReply($conversation, $request->user(), (string) $data['body']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['body' => $exception->getMessage()]);
        }

        return back()->with('status', 'Poruka je poslata.');
    }
}
