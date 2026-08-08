<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DeliveryNoteMigrationContractTest extends TestCase
{
    public function test_hotfix_converts_legacy_document_enum_to_expandable_string(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_07_30_000018_fix_delivery_note_document_type_beta7_9.php'));

        self::assertIsString($migration);
        self::assertStringContainsString('ALTER TABLE `order_documents` MODIFY `document_type` VARCHAR(40) NOT NULL', $migration);
        self::assertStringContainsString("'delivery_note'", file_get_contents(base_path('app/Services/OrderDocumentService.php')) ?: '');
    }
}
