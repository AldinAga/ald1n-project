<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AfterSalesAttachment;
use App\Models\User;
use App\Services\AfterSalesAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AfterSalesAttachmentController extends Controller
{
    public function __invoke(
        Request $request,
        AfterSalesAttachment $attachment,
        AfterSalesAccessService $access,
    ): StreamedResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User && $actor->hasPermission('after_sales.manage'), 403);
        $attachment->load(['case.order', 'message']);
        $access->authorizeManage($attachment->case, $actor);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
