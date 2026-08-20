<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// V0_8_PRODUCT_STATUS_LIGHTWEIGHT_CONTROL_BATCH2
final class ProductStatusService
{
    public function __construct(
        private readonly CatalogAccessService $access,
        private readonly ProductCompletenessService $completeness,
        private readonly ProductAnnouncementService $announcements,
        private readonly AuditLogger $audit,
    ) {}

    /** @return array{product:Product,changed:bool,announcement_count:int} */
    public function change(Product $product, User $actor, string $status): array
    {
        $status = trim($status);
        if (!in_array($status, ['draft', 'active', 'inactive'], true)) {
            throw ValidationException::withMessages(['status' => 'Izabrani status artikla nije dozvoljen.']);
        }

        abort_unless($this->access->canManage($product, $actor), 404);
        abort_if($product->deleted_at !== null || (string) $product->status === 'archived', 422, 'Arhivirani artikal prvo vrati iz arhive.');

        $changed = false;
        $previousStatus = (string) $product->status;

        $updated = DB::transaction(function () use ($product, $actor, $status, &$changed, &$previousStatus): Product {
            $locked = Product::query()->whereKey($product->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($this->access->canManage($locked, $actor), 404);
            abort_if($locked->deleted_at !== null || (string) $locked->status === 'archived', 422, 'Arhivirani artikal prvo vrati iz arhive.');

            $previousStatus = (string) $locked->status;
            if ($previousStatus === $status) return $locked;

            if ($status === 'active') {
                $result = $this->completeness->recalculate($locked, false);
                $locked->refresh()->load('type');
                $percent = (int) ($result['percent'] ?? $locked->completeness_percent ?? 0);
                $minimum = max(0, min(100, (int) ($locked->type?->minimum_completeness_percent ?? 0)));
                if ($percent < $minimum) {
                    $missing = array_values(array_filter(array_map(static fn ($value): string => trim((string) $value), (array) ($result['missing'] ?? []))));
                    $suffix = $missing !== [] ? ' Nedostaje: '.implode(', ', array_slice($missing, 0, 8)).'.' : '';
                    throw ValidationException::withMessages([
                        'status' => 'Artikal ima '.$percent.'% kompletnosti, a za aktivaciju je potrebno najmanje '.$minimum.'%.'.$suffix,
                    ]);
                }
            }

            $before = $locked->only(['id','sku','name','status','completeness_percent','updated_by','locally_modified_at']);
            $locked->forceFill([
                'status' => $status,
                'updated_by' => $actor->id,
                'locally_modified_at' => now(),
            ])->save();
            $changed = true;
            $fresh = $locked->fresh() ?? $locked;
            $this->audit->log(
                'product.status_changed',
                'Promenjen status artikla '.$fresh->sku.' sa '.$previousStatus.' na '.$status.'.',
                $fresh,
                before: $before,
                after: $fresh->only(['id','sku','name','status','completeness_percent','updated_by','locally_modified_at']),
                metadata: ['source' => 'catalog_quick_status'],
                user: $actor,
            );
            return $fresh;
        }, 3);

        $announcementCount = $changed && $previousStatus !== 'active' && (string) $updated->status === 'active'
            ? $this->announcements->queueForNewlyPublished($updated, $actor)
            : 0;

        return ['product' => $updated, 'changed' => $changed, 'announcement_count' => $announcementCount];
    }
}
