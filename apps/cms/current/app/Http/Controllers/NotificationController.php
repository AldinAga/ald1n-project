<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->latest()->paginate(40),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /** Header bell dropdown: the last 10 notifications of the signed-in user and the unread count. */
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = $user->notifications()->latest()->limit(10)->get()->map(static fn ($n): array => [
            'id' => (string) $n->id,
            'title' => (string) ($n->data['title'] ?? 'Obaveštenje'),
            'message' => mb_strimwidth((string) ($n->data['message'] ?? ''), 0, 140, '…'),
            'read' => $n->read_at !== null,
            'time' => $n->created_at?->format('d.m.Y H:i'),
            'read_url' => route('notifications.read', $n->id),
        ])->values();

        return response()->json(['unread' => $user->unreadNotifications()->count(), 'items' => $items]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $entry = $request->user()->notifications()->findOrFail($notification);
        $entry->markAsRead();
        $url = trim((string) ($entry->data['url'] ?? ''));
        return $url !== '' ? redirect()->to($url) : back()->with('status', 'Obaveštenje je označeno kao pročitano.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        return back()->with('status', 'Sva obaveštenja su označena kao pročitana.');
    }
}
