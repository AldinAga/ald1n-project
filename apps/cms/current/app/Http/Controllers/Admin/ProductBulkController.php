<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\SpecificationField;
use App\Services\CatalogAccessService;
use App\Services\ProductBulkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ProductBulkController extends Controller
{
    public function __construct(private readonly CatalogAccessService $catalogAccess) {}

    public function index(Request $request): View
    {
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', (array) $request->query('product_ids', []))))), 0, 500);
        $ids = $this->manageableIds($ids, $request);

        return $this->view(['product_ids' => $ids], $request);
    }

    public function process(Request $request, ProductBulkService $service): View|RedirectResponse
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1', 'max:500'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'mode' => ['required', Rule::in(['preview', 'execute'])],
            'apply_status' => ['nullable', 'boolean'],
            'status' => ['nullable', 'required_if:apply_status,1', Rule::in(['draft', 'active', 'inactive'])],
            'apply_brand' => ['nullable', 'boolean'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'apply_line' => ['nullable', 'boolean'],
            'product_line_id' => ['nullable', 'integer', 'exists:product_lines,id'],
            'apply_type' => ['nullable', 'boolean'],
            'product_type_id' => ['nullable', 'integer', 'exists:product_types,id'],
            'price_action' => ['nullable', Rule::in(['set', 'increase_percent', 'decrease_percent', 'increase_fixed', 'decrease_fixed'])],
            'price_value' => ['nullable', 'required_with:price_action', 'numeric', 'min:0', 'max:9999999999.99'],
            'specification_action' => ['nullable', Rule::in(['set', 'clear'])],
            'specification_field_id' => ['nullable', 'required_with:specification_action', 'integer', 'exists:specification_fields,id'],
            'specification_value' => ['nullable', 'required_if:specification_action,set', 'string', 'max:1000'],
            'specification_detail' => ['nullable', 'string', 'max:500'],
            'regenerate_names' => ['nullable', 'boolean'],
        ]);

        if ($data['mode'] === 'execute') {
            $count = $service->execute($data, $request->user());
            return redirect()->route('catalog.index', ['ownership' => 'mine'])->with('status', 'Bulk izmena je završena za '.$count.' artikala.');
        }

        $data['product_ids'] = $this->manageableIds(array_map('intval', $data['product_ids']), $request, true);

        return $this->view($data + ['preview' => $service->preview($data, $request->user())], $request);
    }

    /** @param array<string,mixed> $data */
    private function view(array $data, ?Request $request = null): View
    {
        $request ??= request();
        $query = Product::query()->whereIn('id', $data['product_ids'] ?? []);
        $this->catalogAccess->applyManageable($query, $request->user());
        $selected = $query->orderBy('name')->get(['id','sku','name','status','completeness_percent']);

        return view('admin.products.bulk', $data + [
            'selectedProducts' => $selected,
            'brands' => Brand::query()->where('status', 'active')->orderBy('name')->get(),
            'lines' => ProductLine::query()->with('brand:id,name')->where('status', 'active')->orderBy('name')->get(),
            'types' => ProductType::query()->with('category:id,name')->where('status', 'active')->orderBy('name')->get(),
            'fields' => SpecificationField::query()->where('status', 'active')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }
    /** @param list<int> $ids @return list<int> */
    private function manageableIds(array $ids, Request $request, bool $abortOnRejected = false): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }

        $query = Product::query()->whereIn('id', $ids);
        $this->catalogAccess->applyManageable($query, $request->user());
        $allowed = $query->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        if ($abortOnRejected && count($allowed) !== count($ids)) {
            abort(403, 'Bulk izmena je dozvoljena samo nad artiklima koje ste kreirali.');
        }

        return $allowed;
    }

}
