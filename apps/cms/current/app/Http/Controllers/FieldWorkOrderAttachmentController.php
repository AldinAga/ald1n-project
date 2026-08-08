<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FieldWorkOrderAttachment;
use App\Services\AfterSalesAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FieldWorkOrderAttachmentController extends Controller
{
    public function __invoke(Request $request, FieldWorkOrderAttachment $attachment, AfterSalesAccessService $access): StreamedResponse
    {
        $attachment->load('workOrder.action.case.order');
        $case = $attachment->workOrder->action->case;
        $user = $request->user();
        $isAdmin = $user->hasRole('admin', 'superadmin');
        if ($isAdmin) {
            $access->authorizeView($case, $user);
        } else {
            abort_unless($attachment->visibility === 'public' && $access->canView($case, $user), 404);
        }
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);
        return Storage::disk('local')->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
