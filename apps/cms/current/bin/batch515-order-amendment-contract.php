<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$project = dirname(dirname(dirname($root)));
$failures = 0;

$check = static function (bool $ok, string $message) use (&$failures): void {
    if ($ok) {
        echo "CONTRACT_PASS $message\n";
        return;
    }
    $failures++;
    echo "CONTRACT_FAIL $message\n";
};

$read = static fn (string $relative): string => is_file($project.'/'.$relative) ? (string) file_get_contents($project.'/'.$relative) : '';

$version = $read('apps/cms/current/app/Services/OrderVersionService.php');
$amend = $read('apps/cms/current/app/Services/OrderAmendmentService.php');
$request = $read('apps/cms/current/app/Http/Requests/UpdateOwnOrderRequest.php');
$resource = $read('apps/cms/current/app/Http/Resources/OrderResource.php');
$routes = $read('apps/cms/current/routes/api.php');
$web = $read('apps/cms/current/routes/web.php');
$docModel = $read('apps/cms/current/app/Models/OrderDocument.php');
$docItem = $read('apps/cms/current/app/Models/OrderDocumentItem.php');
$docService = $read('apps/cms/current/app/Services/OrderDocumentService.php');
$migration = $read('apps/cms/current/database/migrations/2026_10_03_000200_create_order_document_items_batch515.php');
$mobileTypes = $read('apps/mobile/current/src/types/api.ts');
$mobileApi = $read('apps/mobile/current/src/lib/api/endpoints.ts');
$mobileEdit = $read('apps/mobile/current/src/app/(app)/order/[id]/edit.tsx');
$adminApi = $read('apps/mobile/current/src/features/admin/orders-admin-api.ts');
$adminActions = $read('apps/mobile/current/src/features/admin/orders-admin-actions.tsx');
$productForm = $read('apps/cms/current/resources/views/admin/products/form.blade.php');
$openapi = $read('packages/api-contract/openapi.yaml');

$check(str_contains($version, 'final class OrderVersionService') && str_contains($version, 'expected_edit_token'), 'order version service and stale token contract');
$check(str_contains($amend, 'final class OrderAmendmentService') && str_contains($amend, 'order.customer_amended') && str_contains($amend, 'order_amendment'), 'transactional order amendment service');
$check(str_contains($request, 'final class UpdateOwnOrderRequest') && str_contains($request, "'expected_edit_token'"), 'customer amendment request validation');
$check(str_contains($resource, "'edit_token'") && str_contains($resource, "'can_amend'"), 'order resource amendment capability');
$check(str_contains($routes, "Route::patch('/orders/{order}'") && str_contains($web, "Route::get('/orders/{order}/edit'"), 'API and Web amendment routes');
$check(str_contains($docModel, 'function items(): HasMany') && str_contains($docItem, 'final class OrderDocumentItem'), 'immutable order document item model relation');
$check(str_contains($migration, "Schema::create('order_document_items'") && str_contains($migration, 'document_item_sequence_unique'), 'document item snapshot migration');
$check(str_contains($docService, 'invalidateIssuedForAmendmentLocked') && str_contains($docService, '$document->items->map') && !str_contains($docService, '$order?->items->map'), 'document lifecycle uses immutable item snapshots');
$check(str_contains($mobileTypes, 'edit_token: string') && str_contains($mobileTypes, 'can_amend: boolean'), 'mobile order amendment types');
$check(str_contains($mobileApi, 'update: async (id: number') && str_contains($mobileApi, "'Idempotency-Key'"), 'mobile amendment API idempotency');
$check(str_contains($mobileEdit, 'Sačuvaj izmene porudžbine') && str_contains($mobileEdit, 'error.status === 409'), 'mobile amendment UX and stale conflict');
$check(str_contains($adminApi, 'order_version_token') && str_contains($adminActions, 'orderVersionToken'), 'mobile admin stale order token');
$check(str_contains($productForm, 'Sačuvaj izmene') && !str_contains($productForm, 'Sa&#269;uvaj izmene') && !str_contains($productForm, 'Sacuvaj izmene'), 'Laravel product form canonical UTF-8 save copy');
$check(str_contains($openapi, 'operationId: updateOrder') && str_contains($openapi, 'expected_edit_token') && str_contains($openapi, "'409'"), 'OpenAPI amendment and conflict contract');

exit($failures === 0 ? 0 : 1);
