<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CommissionPercentagePolicyContractTest extends TestCase
{
    public function test_active_commission_contract_uses_ten_percent_without_fixed_twenty_euro_floor(): void
    {
        $root = dirname(__DIR__, 2);
        $calculator = (string) file_get_contents($root.'/app/Services/CommissionCalculator.php');
        $orders = (string) file_get_contents($root.'/app/Services/OrderService.php');
        $request = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
        $ownView = (string) file_get_contents($root.'/resources/views/commissions/index.blade.php');
        $adminForm = (string) file_get_contents($root.'/resources/views/admin/products/form.blade.php');
        $static = (string) file_get_contents($root.'/bin/static-check.php');
        $openapi = (string) file_get_contents(dirname($root, 3).'/packages/api-contract/openapi.yaml');
        $directSale = (string) file_get_contents($root.'/app/Services/DirectSaleService.php');
        $mobileCreate = (string) file_get_contents(dirname($root, 3).'/apps/mobile/current/src/app/(app)/admin/catalog/create.tsx');
        $migration = (string) file_get_contents($root.'/database/migrations/2026_08_19_222500_remove_fixed_commission_snapshot_defaults_v0_7.php');
        $orderShow = (string) file_get_contents($root.'/resources/views/orders/show.blade.php');
        $catalogAccessTest = (string) file_get_contents($root.'/tests/Feature/CatalogAccessTest.php');
        $domainSmoke = (string) file_get_contents($root.'/bin/domain-smoke.php');

        self::assertStringContainsString('COMMISSION_PERCENTAGE_POLICY_V0_7', $calculator);
        self::assertStringContainsString('DEFAULT_RATE_PERCENT = 10.0', $calculator);
        self::assertStringContainsString('AUTOMATIC_MAX_EUR = 50.0', $calculator);
        self::assertStringContainsString('manualMinimumEur', $calculator);
        self::assertStringContainsString('usesManual', $calculator);
        self::assertStringNotContainsString('max(20.0', $calculator);
        self::assertStringNotContainsString('$manualEur >= 20.0', $calculator);

        self::assertStringContainsString('COMMISSION_PERCENTAGE_POLICY_V0_7', $orders);
        self::assertStringContainsString('$usesManualCommission', $orders);
        self::assertStringContainsString("'commission_source_snapshot' => \$usesManualCommission ? 'manual' : 'automatic'", $orders);
        self::assertStringContainsString("'commission_rate_percent_snapshot' => \$usesManualCommission ? null : CommissionCalculator::DEFAULT_RATE_PERCENT", $orders);
        self::assertStringNotContainsString('$manualCommission >= 20.0', $orders);

        self::assertStringContainsString('COMMISSION_PERCENTAGE_POLICY_V0_7', $request);
        self::assertStringContainsString('manualMinimumEur', $request);
        self::assertStringContainsString('10%% vrednosti artikla', $request);

        self::assertStringContainsString('Podrazumevana provizija je 10% vrednosti artikla po komadu.', $ownView);
        self::assertStringNotContainsString('nikada nije manja od 20 EUR', $ownView);
        self::assertStringContainsString('Minimalna ručna provizija je 10% vrednosti artikla preračunate u EUR.', $adminForm);
        self::assertStringContainsString('podrazumevanih 10 procenata', $static);
        self::assertStringNotContainsString('minimum 20 EUR', $static);
        self::assertStringContainsString('at least 10% of the product value converted to EUR', $openapi);
        self::assertStringContainsString('MOBILE_V0_7_COMMISSION_PERCENTAGE_POLICY', $mobileCreate);
        self::assertStringContainsString('Prazno polje koristi automatskih 10% vrednosti artikla', $mobileCreate);
        self::assertStringContainsString('Podrazumevana provizija po komadu iznosi 10% vrednosti artikla.', $orderShow);
        self::assertStringNotContainsString('Provizija po komadu nije manja od 20 EUR.', $orderShow);
        self::assertStringContainsString("assertJsonPath('data.commission_eur', 10)", $catalogAccessTest);
        self::assertStringNotContainsString("assertJsonPath('data.commission_eur', 20)", $catalogAccessTest);
        self::assertStringContainsString('COMMISSION_PERCENTAGE_POLICY_V0_7', $domainSmoke);
        self::assertStringContainsString('20 EUR artikal daje 2 EUR provizije', $domainSmoke);
        self::assertStringContainsString('commission_unit_eur_snapshot` SET DEFAULT 0.00', $migration);
        self::assertStringContainsString('commission_total_eur_snapshot` SET DEFAULT 0.00', $migration);
        self::assertStringNotContainsString('SET DEFAULT 20.00', $migration);

        self::assertStringContainsString("'commission_source_snapshot' => 'direct_sale'", $directSale);
        self::assertStringContainsString("'commission_rate_percent_snapshot' => 0", $directSale);
        self::assertStringContainsString("'commission_unit_eur_snapshot' => 0", $directSale);
        self::assertStringContainsString("'commission_total_eur_snapshot' => 0", $directSale);
    }
}
