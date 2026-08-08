<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class OrderEmailIpsMigrationContractTest extends TestCase
{
    public function test_beta716_migration_is_repeatable_and_contains_required_snapshots(): void
    {
        $path = dirname(__DIR__, 2).'/database/migrations/2026_07_30_000025_create_order_email_outbox_beta7_16.php';
        $source = (string) file_get_contents($path);

        self::assertStringContainsString('createOrRepairOrderEmailOutbox', $source);
        self::assertStringContainsString("Schema::hasTable('order_email_outbox')", $source);
        self::assertStringContainsString('Schema::getIndexes', $source);
        self::assertStringContainsString('Schema::getForeignKeys', $source);
        self::assertStringContainsString('order_email_outbox_dedupe_key_unique', $source);
        self::assertStringContainsString('ips_payload_snapshot', $source);
        self::assertStringContainsString('ips_qr_image_path', $source);
        self::assertStringContainsString('duration_days', $source);
        self::assertStringNotContainsString('use Throwable;', $source);
    }
}
