<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DocumentRevisionMigrationContractTest extends TestCase
{
    public function test_revision_migration_removes_single_document_per_type_constraint(): void
    {
        $migration = file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_07_30_000023_enable_document_revisions_beta7_14.php');

        self::assertIsString($migration);
        self::assertStringContainsString('dropOrderTypeUniqueIndexes', $migration);
        self::assertStringContainsString('ensureOrderIdForeignKeySupportIndex', $migration);
        self::assertStringContainsString('order_documents_order_id_fk_index', $migration);
        self::assertLessThan(
            strpos($migration, 'dropOrderTypeUniqueIndexes();'),
            strpos($migration, 'ensureOrderIdForeignKeySupportIndex();'),
            'FK support indeks mora biti obezbeđen pre uklanjanja starog unique indeksa.',
        );
        self::assertStringContainsString('revision_number', $migration);
        self::assertStringContainsString('supersedes_document_id', $migration);
        self::assertStringContainsString('cancellation_reason', $migration);
        self::assertStringContainsString("['order_id', 'document_type', 'status']", $migration);
    }
}
