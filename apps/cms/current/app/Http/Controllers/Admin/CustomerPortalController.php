<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PortalConversation;
use App\Models\PortalMessage;
use App\Models\PortalOrderLinkHistory;
use App\Models\ProductWarranty;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\AuditLogger;
use App\Services\CustomerActivationService;
use App\Services\CustomerPortalAdminService;
use App\Services\PortalSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

final class CustomerPortalController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->whereHas('role', static fn ($roles) => $roles->where('slug', 'user'))
            ->with(['role', 'group', 'latestActivationToken'])
            ->withCount(['orders', 'portalConversations'])
            ->latest('id');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(static function ($users) use ($search): void {
                $users->where('username', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%');
            });
        }
        if (in_array($request->query('status'), ['pending', 'active', 'blocked'], true)) {
            $query->where('status', $request->query('status'));
        }

        $conversationAvailable = Schema::hasTable('portal_conversations') && Schema::hasTable('portal_messages');
        $conversations = $conversationAvailable
            ? PortalConversation::query()
                ->with(['customer:id,first_name,last_name,username,email', 'order:id,order_number', 'assignee:id,first_name,last_name,username', 'latestPublicMessage.sender:id,first_name,last_name,username'])
                ->withCount(['publicMessages as unread_staff_count' => static fn ($messages) => $messages->whereNull('read_by_staff_at')])
                ->latest('last_message_at')
                ->limit(12)
                ->get()
            : collect();

        return view('admin.customer-portal.index', [
            'users' => $query->paginate(25)->withQueryString(),
            'conversations' => $conversations,
            'statusLabels' => PortalConversation::statusLabels(),
            'stats' => [
                'pending_users' => User::query()->where('status', 'pending')->whereHas('role', static fn ($r) => $r->where('slug', 'user'))->count(),
                'active_users' => User::query()->where('status', 'active')->whereHas('role', static fn ($r) => $r->where('slug', 'user'))->count(),
                'open_conversations' => $conversationAvailable ? PortalConversation::query()->where('status', '!=', 'closed')->count() : 0,
                'unread_messages' => $conversationAvailable ? PortalMessage::query()->where('visibility', 'public')->whereNull('read_by_staff_at')->count() : 0,
            ],
            'conversationAvailable' => $conversationAvailable,
        ]);
    }

    public function store(
        Request $request,
        CustomerPortalAdminService $portal,
    ): RedirectResponse {
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
        $user = $result['user'];
        if (!$result['invitation_sent']) {
            return redirect()->route('admin.customer-portal.users.show', $user)
                ->withErrors(['invitation' => 'Korisnik je kreiran, ali poziv nije poslat: '.$result['invitation_error']]);
        }
        return redirect()->route('admin.customer-portal.users.show', $user)
            ->with('status', 'Kupac je kreiran i aktivacioni poziv je poslat.');
    }
    public function show(Request $request, User $user, PortalSessionService $sessions): View
    {
        abort_unless($user->hasRole('user'), 404);
        $user->load(['role', 'group', 'latestActivationToken']);
        $orders = Order::query()->where('user_id', $user->id)->latest('id')->limit(50)->get();
        $orderSearch = collect();
        if ($search = trim((string) $request->query('order_q'))) {
            $orderSearch = Order::query()
                ->with('user:id,first_name,last_name,username,email')
                ->where(static function ($orders) use ($search): void {
                    $orders->where('order_number', 'like', '%'.$search.'%')
                        ->orWhere('shipping_full_name', 'like', '%'.$search.'%')
                        ->orWhere('shipping_phone', 'like', '%'.$search.'%');
                })
                ->latest('id')
                ->limit(30)
                ->get();
        }

        return view('admin.customer-portal.show', [
            'customer' => $user,
            'orders' => $orders,
            'orderSearch' => $orderSearch,
            'activeSessions' => $sessions->activeFor($user, $request),
            'conversations' => Schema::hasTable('portal_conversations')
                ? PortalConversation::query()->where('user_id', $user->id)->latest('last_message_at')->limit(20)->get()
                : collect(),
            'statusLabels' => PortalConversation::statusLabels(),
        ]);
    }

    public function invite(
        Request $request,
        User $user,
        CustomerPortalAdminService $portal,
    ): RedirectResponse {
        abort_unless($user->hasRole('user'), 404);
        try {
            $portal->invite($user, $request->user());
        } catch (Throwable $exception) {
            return back()->withErrors(['invitation' => 'Poziv nije poslat: '.$exception->getMessage()]);
        }
        return back()->with('status', 'Aktivacioni poziv je poslat na '.$user->email.'.');
    }
    public function linkOrder(
        Request $request,
        User $user,
        CustomerPortalAdminService $portal,
    ): RedirectResponse {
        abort_unless($user->hasRole('user'), 404);
        $data = $request->validate([
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'confirm_reassign' => ['nullable', 'accepted'],
            'move_related_portal_data' => ['nullable', 'boolean'],
        ]);
        $result = $portal->linkOrder(
            $user,
            (int) $data['order_id'],
            (string) $data['reason'],
            $request->boolean('confirm_reassign'),
            $request->boolean('move_related_portal_data'),
            $request->user(),
        );
        if (!$result['changed']) {
            return back()->with('status', 'Porudžbina je već povezana sa ovim kupcem.');
        }
        return back()->with('status', 'Porudžbina '.$result['order']->order_number.' je povezana sa kupcem '.$user->displayName().'.');
    }
    public function revokeSessions(
        Request $request,
        User $user,
        CustomerPortalAdminService $portal,
    ): RedirectResponse {
        abort_unless($user->hasRole('user'), 404);
        $result = $portal->revokeSessions($user, $request->user());
        return back()->with(
            'status',
            'Opozvano aktivnih prijava: '.($result['revoked_web_sessions'] + $result['revoked_api_sessions']).'.',
        );
    }
    private function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'));
        $base = mb_substr($base !== '' ? $base : 'kupac', 0, 42);
        $candidate = $base;
        $suffix = 1;
        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = mb_substr($base, 0, 42).'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
