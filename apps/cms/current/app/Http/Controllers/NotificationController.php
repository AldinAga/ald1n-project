<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
