<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(10, min(100, (int) $request->integer('per_page', 40)));
        $query = $request->user()->notifications()->latest();
        if ($request->boolean('unread')) {
            $query->whereNull('read_at');
        }

        return NotificationResource::collection($query->paginate($perPage)->withQueryString());
    }

    public function read(Request $request, string $notification): NotificationResource
    {
        $entry = $request->user()->notifications()->findOrFail($notification);
        if ($entry->read_at === null) {
            $entry->markAsRead();
        }

        return new NotificationResource($entry->refresh());
    }

    public function readAll(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();
        if ($count > 0) {
            $request->user()->unreadNotifications()->update(['read_at' => now()]);
        }

        return response()->json(['data' => [
            'marked_read' => $count,
            'unread' => 0,
        ]]);
    }
}
