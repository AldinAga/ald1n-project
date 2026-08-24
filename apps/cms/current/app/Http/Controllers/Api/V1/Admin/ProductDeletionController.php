<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\CatalogAccessService;
use App\Services\ProductDeletionService;
use App\Services\TotalProductPurgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

// MOBILE_V1_0_ADMIN_CATALOG_PURGE_TOTAL_PURGE_BATCH24
final class ProductDeletionController extends Controller
{
    public function __construct(
        private readonly CatalogAccessService $catalogAccess,
        private readonly ProductDeletionService $deletions,
    ) {}

    public function show(Request $request, Product $product): JsonResponse
    {
        $actor = $this->actor($request);
        $this->authorizeProduct($product, $actor);

        $blockers = $this->deletions->blockers($product);
        $isSuperAdmin = $actor->hasRole('superadmin');
        $isArchived = $product->deleted_at !== null && (string) $product->status === 'archived';

        return response()->json(['data' => [
            'product_id' => (int) $product->id,
            'sku' => (string) $product->sku,
            'name' => (string) $product->name,
            'is_archived' => $isArchived,
            'blockers' => $blockers,
            'purge_available' => $blockers === [],
            'delete_images_default' => true,
            'total_purge_available' => $isSuperAdmin && $isArchived,
            'total_purge_irreversible_confirmation' => $isSuperAdmin && $isArchived
                ? TotalProductPurgeService::IRREVERSIBLE_CONFIRMATION
                : null,
            'total_purge_reason_min_length' => 10,
            'total_purge_reason_max_length' => 1000,
            'retention_notice' => 'Disaster-recovery backupi i off-host kopije se ne brišu Total Product Purge akcijom i predstavljaju zasebnu retention granicu.',
        ]]);
    }

    public function purge(Request $request, Product $product): JsonResponse
    {
        $actor = $this->actor($request);
        $this->authorizeProduct($product, $actor);

        $data = $request->validate([
            'confirmation' => ['required', 'string', 'max:100'],
            'delete_images' => ['nullable', 'boolean'],
        ]);

        if (!hash_equals((string) $product->sku, trim((string) $data['confirmation']))) {
            throw ValidationException::withMessages([
                'confirmation' => 'Za trajno brisanje upiši tačnu šifru artikla: '.$product->sku.'.',
            ]);
        }

        $result = $this->deletions->purge($product, $actor, $request->boolean('delete_images'));
        $message = 'Artikal je trajno obrisan.';

        if ($result['files_requested']) {
            $message .= $result['files_deleted']
                ? ' Lokalne slike su uklonjene sa servera.'
                : ' Zapisi slika su obrisani, ali proveri storage jer direktorijum nije potpuno uklonjen.';
        } else {
            $message .= ' Fajlovi slika su ostavljeni na serveru po izabranoj opciji.';
        }

        if ($result['legacy_images'] > 0) {
            $message .= ' Legacy slike ('.$result['legacy_images'].') nisu fizički brisane jer pripadaju read-only izvoru.';
        }

        return response()->json([
            'message' => $message,
            'data' => $result,
        ]);
    }

    public function totalPurge(
        Request $request,
        Product $product,
        TotalProductPurgeService $purge,
    ): JsonResponse {
        $actor = $this->actor($request);
        $this->authorizeProduct($product, $actor);
        abort_unless($actor->hasRole('superadmin'), 403);

        $data = $request->validate([
            'total_confirmation' => ['required', 'string', 'max:100'],
            'total_reason' => ['required', 'string', 'min:10', 'max:1000'],
            'total_irreversible_confirmation' => ['required', 'string', 'max:100'],
            'total_retention_acknowledged' => ['required', 'boolean', 'accepted'],
        ]);

        $result = $purge->purge($product, $actor, [
            'confirmation' => (string) $data['total_confirmation'],
            'reason' => (string) $data['total_reason'],
            'irreversible_confirmation' => (string) $data['total_irreversible_confirmation'],
            'retention_acknowledged' => (bool) $data['total_retention_acknowledged'],
        ]);

        return response()->json([
            'message' => 'Total Product Purge je završen. Artikal i njegovi live identitetski tragovi su uklonjeni; normalan restore više ne postoji. Disaster-recovery backupi i off-host kopije ostaju zasebna retention granica.',
            'data' => $result,
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        return $actor;
    }

    private function authorizeProduct(Product $product, User $actor): void
    {
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);
    }
}
