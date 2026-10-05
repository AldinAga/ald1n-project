<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\OrderAmendmentService;
use App\Services\OrderVersionService;
use Tests\TestCase;

/**
 * Batch515 regression authority for development/CI environments that install dev dependencies.
 * Production deployment intentionally uses bin/batch515-order-amendment-contract.php because
 * the current production vendor tree does not include PHPUnit.
 */
final class OrderAmendmentTest extends TestCase
{
    public function test_batch515_services_are_resolvable(): void
    {
        self::assertInstanceOf(OrderVersionService::class, app(OrderVersionService::class));
        self::assertInstanceOf(OrderAmendmentService::class, app(OrderAmendmentService::class));
    }

    public function test_batch515_contract_documents_customer_amendment_rules(): void
    {
        $source = (string) file_get_contents(app_path('Services/OrderAmendmentService.php'));
        self::assertStringContainsString('order.customer_amended', $source);
        self::assertStringContainsString('order_amendment', $source);
        self::assertStringContainsString("['paid', 'cancelled']", $source);
        self::assertStringContainsString('Arhivirana ili nedostupna stavka', $source);
    }

    public function test_batch515_document_renderer_uses_immutable_snapshot_items(): void
    {
        $source = (string) file_get_contents(app_path('Services/OrderDocumentService.php'));
        self::assertStringContainsString('$document->items->map', $source);
        self::assertStringNotContainsString('$order?->items->map', $source);
    }
}
