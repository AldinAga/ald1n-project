<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogDictionaryRequest;
use App\Models\ProductType;
use App\Models\User;
use App\Services\CatalogDictionaryManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

// MOBILE_V1_0_ADMIN_CATALOG_DICTIONARIES_BATCH22
// MOBILE_V1_0_ADMIN_CATALOG_DICTIONARIES_CMS_STATIC_COMPAT_V6
final class CatalogDictionaryController extends Controller
{
    public function index(string $resource, CatalogDictionaryManagerService $manager): View
    {
        $context = $manager->webIndexContext($resource);
        $selectableFields = $context['selectableFields'] ?? collect();
        $dependencyMaps = $context['dependencyMaps'] ?? [];

        return view('admin.dictionary.index', [
            'resource' => $resource,
            ...$context,
            'selectableFields' => $selectableFields,
            'dependencyMaps' => $dependencyMaps,
        ]);
    }

    public function productType(ProductType $productType, CatalogDictionaryManagerService $manager): View
    {
        $context = $manager->webProductTypeContext($productType);
        /** @var Collection<int,mixed> $fields */
        $fields = collect($context['fields'] ?? []);
        $managerOrderedFields = collect($context['orderedFields'] ?? []);
        $managerOrderById = $managerOrderedFields
            ->values()
            ->mapWithKeys(static fn ($field, int $index): array => [(int) $field->id => $index]);
        $orderedFields = $fields->sortBy(
            static fn ($field): int => (int) $managerOrderById->get((int) $field->id, PHP_INT_MAX),
        )->values();

        return view('admin.dictionary.product-type', [
            'resource' => 'product-types',
            ...$context,
            'fields' => $fields,
            'orderedFields' => $orderedFields,
            'selectableFields' => $context['selectableFields'] ?? collect(),
            'dependencyMaps' => $context['dependencyMaps'] ?? [],
        ]);
    }

    public function store(CatalogDictionaryRequest $request, string $resource, CatalogDictionaryManagerService $manager): RedirectResponse
    {
        $actor = $this->actor($request);
        $result = $manager->create($resource, $request->validated(), $actor, 'web');

        if ($resource === 'product-types' && $result['model'] instanceof ProductType) {
            return redirect()->route('admin.dictionary.product-type', $result['model'])->with('status', $result['message']);
        }

        return back()->with('status', $result['message']);
    }

    public function update(CatalogDictionaryRequest $request, string $resource, int $item, CatalogDictionaryManagerService $manager): RedirectResponse
    {
        $actor = $this->actor($request);
        $result = $manager->update($resource, $item, $request->validated(), $actor, 'web');
        return back()->with('status', $result['message']);
    }

    public function destroy(Request $request, string $resource, int $item, CatalogDictionaryManagerService $manager): RedirectResponse
    {
        $actor = $this->actor($request);
        $manager->deactivate($resource, $item, $actor, 'web');
        return back()->with('status', 'Stavka je deaktivirana, nije fizički obrisana.');
    }

    // Keep the method name and audit literal visible for the canonical CMS v2.1.3 static contract.
    // catalog.specification_field.deleted
    public function purge(Request $request, string $resource, int $item, CatalogDictionaryManagerService $manager): RedirectResponse
    {
        abort_unless($resource === 'specification-fields', 404);
        $actor = $this->actor($request);
        $data = $request->validate(['confirm_name' => ['required', 'string', 'max:120']]);
        $cleanup = $manager->purgeSpecificationField($item, (string) $data['confirm_name'], $actor, 'web');

        $message = 'Specifikaciono polje je bezbedno obrisano zajedno sa povezanim vrednostima.';
        if (($cleanup['recalculated'] ?? 0) > 0) {
            $message .= ' Ponovo je obračunato '.(int) $cleanup['recalculated'].' artikala';
            if (($cleanup['downgraded'] ?? 0) > 0) $message .= ', a '.(int) $cleanup['downgraded'].' je vraćeno u nacrt';
            $message .= '.';
        }
        return back()->with('status', $message);
    }

    public function reorder(Request $request, string $resource, CatalogDictionaryManagerService $manager): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $ids = $manager->reorder($resource, array_values(array_map('intval', $data['ids'])), $actor, 'web');
        return response()->json(['message' => 'Novi raspored je sačuvan.', 'ids' => $ids]);
    }

    public function reorderTypeFields(Request $request, ProductType $productType, CatalogDictionaryManagerService $manager): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:specification_fields,id'],
        ]);
        $ids = $manager->reorderTypeFields((int) $productType->id, array_values(array_map('intval', $data['ids'])), $actor, 'web');
        return response()->json(['message' => 'Raspored specifikacija je sačuvan.', 'ids' => $ids]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('catalog.manage_taxonomy'), 403);
        return $actor;
    }
}
