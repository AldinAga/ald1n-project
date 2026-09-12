<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$read = static function (string $path) use (&$failures): string {
    if (!is_file($path)) {
        $failures[] = 'missing_file='.$path;
        return '';
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $failures[] = 'unreadable_file='.$path;
        return '';
    }

    return $content;
};

$contains = static function (string $label, string $needle, string $source) use (&$failures): void {
    if (!str_contains($source, $needle)) {
        $failures[] = $label.' missing='.$needle;
    }
};

$notContains = static function (string $label, string $needle, string $source) use (&$failures): void {
    if (str_contains($source, $needle)) {
        $failures[] = $label.' forbidden='.$needle;
    }
};

$controller = $read($root.'/app/Http/Controllers/Api/V1/Admin/CommissionController.php');
$mobileApi = $read($root.'/../../mobile/current/src/features/admin/commissions-admin-api.ts');
$mobileList = $read($root.'/../../mobile/current/src/app/(app)/admin/commissions/index.tsx');
$mobileDetail = $read($root.'/../../mobile/current/src/app/(app)/admin/commissions/[id].tsx');
$openapi = $read($root.'/docs/openapi.yaml');

$contains('backend.subtotal', "'subtotal_rsd' =>", $controller);
$contains('backend.items_relation', "'order.items'", $controller);
$contains('backend.breakdown', "'commission_breakdown'", $controller);
$contains('backend.snapshot', 'commission_total_eur_snapshot', $controller);
$contains('backend.adjustment', "'adjustment_eur'", $controller);
$contains('backend.has_adjustment', "'has_adjustment'", $controller);

$contains('mobile_api.line_item', 'export type AdminCommissionLineItem', $mobileApi);
$contains('mobile_api.breakdown', 'export type AdminCommissionBreakdown', $mobileApi);
$contains('mobile_api.subtotal', 'subtotal_rsd: number', $mobileApi);
$contains('mobile_api.breakdown_field', 'commission_breakdown?: AdminCommissionBreakdown', $mobileApi);

$contains('mobile_list.default_commissions', "useState<CommissionListWorkspace> ('commissions')", $mobileList);
$notContains('mobile_list.no_overview_option', "{ value: 'overview', label: 'Pregled'", $mobileList);
$contains('mobile_list.order_total', "formatMoney(item.order.subtotal_rsd, 'RSD')", $mobileList);
$contains('mobile_list.order_accessibility', 'accessibilityLabel={`Otvori porudžbinu ${item.order.order_number}`}', $mobileList);
$contains('mobile_list.order_pressable', '<Pressable accessibilityRole="button" accessibilityLabel={`Otvori porudžbinu ${item.order.order_number}`} onPress={onOpen}>', $mobileList);
$contains('mobile_list.summary', 'Sažetak provizija', $mobileList);

$contains('mobile_detail.marker', 'BATCH156_SINGLE_PAGE_COMMISSION', $mobileDetail);
$contains('mobile_detail.breakdown', 'commission.commission_breakdown', $mobileDetail);
$contains('mobile_detail.calculation', 'Obračun provizije', $mobileDetail);
$contains('mobile_detail.pay', 'POTVRDI ISPLATU PROVIZIJE', $mobileDetail);
$contains('mobile_detail.approve', 'ODOBRI PROVIZIJU', $mobileDetail);
$contains('mobile_detail.history', 'Istorija statusa', $mobileDetail);
$notContains('mobile_detail.no_workspace_type', 'CommissionDetailWorkspace', $mobileDetail);
$notContains('mobile_detail.no_workspace_options', 'COMMISSION_DETAIL_WORKSPACE_OPTIONS', $mobileDetail);

$contains('openapi.subtotal', 'subtotal_rsd: { type: number', $openapi);
$contains('openapi.breakdown', 'commission_breakdown:', $openapi);
$contains('openapi.snapshot', 'commission_total_eur_snapshot:', $openapi);
$contains('openapi.adjustment', 'adjustment_eur:', $openapi);
$contains('openapi.has_adjustment', 'has_adjustment:', $openapi);

if ($failures !== []) {
    foreach ($failures as $failure) {
        fwrite(STDERR, 'CONTRACT_FAIL '.$failure.PHP_EOL);
    }
    exit(1);
}

echo 'ADMIN_COMMISSION_SINGLE_PAGE_CONTRACT=PASS'.PHP_EOL;
