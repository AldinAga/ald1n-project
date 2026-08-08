<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class OrderCostSnapshotRepairContractTest extends TestCase
{
    public function test_repair_is_conservative_and_uses_audited_sources(): void
    {
        $root = dirname(__DIR__, 2);
        $service = (string) file_get_contents($root.'/app/Services/OrderItemCostSnapshotService.php');

        self::assertStringContainsString('only touches incomplete snapshots', $service);
        self::assertStringContainsString('lockForUpdate()', $service);
        self::assertStringContainsString("'repair_variant_current'", $service);
        self::assertStringContainsString("'repair_receipt_historical'", $service);
        self::assertStringContainsString("'repair_product_current'", $service);
        self::assertStringContainsString('order_item.cost_snapshot.repaired', $service);
        self::assertStringContainsString('order_item.cost_snapshot.manual', $service);
    }

    public function test_manual_financial_change_requires_reason_and_audit_table(): void
    {
        $service = (string) file_get_contents(dirname(__DIR__, 2).'/app/Services/OrderItemCostSnapshotService.php');

        self::assertStringContainsString('mb_strlen($reason) < 5', $service);
        self::assertStringContainsString("Schema::hasTable('audit_logs')", $service);
        self::assertStringContainsString("'manual_console'", $service);
    }

    public function test_management_reports_repair_invokes_snapshot_repair(): void
    {
        $doctor = (string) file_get_contents(dirname(__DIR__, 2).'/app/Console/Commands/ManagementReportsDoctorCommand.php');

        self::assertStringContainsString('OrderItemCostSnapshotService $costSnapshots', $doctor);
        self::assertStringContainsString('$costSnapshots->repairMissing()', $doctor);
        self::assertStringContainsString('$costSnapshots->missingCount()', $doctor);
        self::assertStringContainsString('app:order-cost-snapshots --repair', $doctor);
    }
}
