<?php

declare(strict_types=1);

$source = dirname(__DIR__).'/app/Support/OrderProductSummary.php';
if (!is_file($source)) {
    fwrite(STDERR, "FAIL OrderProductSummary is not implemented\n");
    exit(1);
}
require_once $source;
$cases = [
    'one snapshot' => [['Dell 7040'], 'Dell 7040', 0],
    'multiple rows' => [['Dell 7040', 'RAM 16 GB', 'SSD 512 GB'], 'Dell 7040', 2],
    'empty order' => [[], 'Stavke nisu dostupne', 0],
    'blank first row' => [['  ', 'Lenovo T14'], 'Artikal bez naziva', 1],
    'trim snapshot' => [['  ThinkPad T14  '], 'ThinkPad T14', 0],
    'unicode snapshot' => [["Ra\u{010d}unar \u{017d} - 16 GB"], "Ra\u{010d}unar \u{017d} - 16 GB", 0],
    'markup stays plain data for escaped Blade output' => [['<img src=x onerror=alert(1)>'], '<img src=x onerror=alert(1)>', 0],
    'count lines not unique names' => [['RAM', 'RAM'], 'RAM', 1],
];
foreach ($cases as $name => [$input, $expected, $others]) {
    $actual = \App\Support\OrderProductSummary::fromNames($input);
    if ($actual !== ['product_name' => $expected, 'other_products' => $others]) {
        fwrite(STDERR, 'FAIL '.$name.' '.json_encode($actual)."\n"); exit(1);
    }
    echo 'PASS '.$name."\n";
}
echo 'ORDER_PRODUCT_SUMMARY=PASS tests='.count($cases)."\n";
