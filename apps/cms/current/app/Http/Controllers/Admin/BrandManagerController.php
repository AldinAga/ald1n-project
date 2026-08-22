<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandManagerRequest;
use App\Models\Brand;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BrandManagerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3
final class BrandManagerController extends Controller
{
    public function index(Request $request, BrandManagerService $service): View
    {
        $q = trim((string) $request->query('q', ''));
        $typeId = $request->filled('product_type_id') ? (int) $request->query('product_type_id') : null;
        $data = $service->managerData($q, $typeId);
        $editId = $request->filled('edit') ? (int) $request->query('edit') : 0;

        return view('admin.brands.index', [
            ...$data,
            'editingBrand' => $editId > 0 ? $service->brandPayload($editId, $data['product_types']) : null,
            'createMode' => $request->boolean('create'),
        ]);
    }

    public function store(BrandManagerRequest $request, BrandManagerService $service, AuditLogger $audit): RedirectResponse
    {
        $actor = $this->actor($request);
        $brand = $service->create($request->validated(), $actor);
        $after = $service->brandPayload((int) $brand->getKey());
        $audit->log(
            'catalog.brand.created',
            'Kreiran globalni brend '.$brand->name,
            $brand,
            after: $after,
            metadata: ['source' => 'global_brand_manager', 'product_type_ids' => $after['product_type_ids']],
            user: $actor,
        );

        return redirect()->route('admin.brand-manager.index', ['edit' => $brand->getKey()])
            ->with('status', 'Brend je kreiran i povezan sa izabranim tipovima.');
    }

    public function update(BrandManagerRequest $request, Brand $brand, BrandManagerService $service, AuditLogger $audit): RedirectResponse
    {
        $actor = $this->actor($request);
        $before = $service->brandPayload((int) $brand->getKey());
        $updated = $service->update($brand, $request->validated(), $actor);
        $after = $service->brandPayload((int) $updated->getKey());
        $audit->log(
            'catalog.brand.updated',
            'Izmenjen globalni brend '.$updated->name,
            $updated,
            before: $before,
            after: $after,
            metadata: ['source' => 'global_brand_manager', 'product_type_ids' => $after['product_type_ids']],
            user: $actor,
        );

        return redirect()->route('admin.brand-manager.index', ['edit' => $updated->getKey()])
            ->with('status', 'Brend je sačuvan. Istorijske veze nisu uklonjene bez provere korišćenja.');
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('catalog.manage_taxonomy'), 403);
        return $actor;
    }
}
