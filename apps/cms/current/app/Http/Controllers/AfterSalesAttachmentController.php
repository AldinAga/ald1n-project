<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AfterSalesAttachment;
use App\Services\AfterSalesAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AfterSalesAttachmentController extends Controller
{
    public function __invoke(Request $request, AfterSalesAttachment $attachment, AfterSalesAccessService $access): StreamedResponse
    {
        $attachment->load(['case.order', 'message']);
        $access->authorizeView($attachment->case, $request->user());
        if ($attachment->message?->visibility === 'internal' && !$request->user()->hasPermission('after_sales.manage')) {
            abort(404);
        }
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
