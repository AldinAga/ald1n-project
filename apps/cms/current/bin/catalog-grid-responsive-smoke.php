#!/usr/bin/env php
<?php
declare(strict_types=1);

// BATCH564_CATALOG_GRID_RESPONSIVE_4321_BEHAVIOR_CONTRACT
// Source-only cascade contract: no Laravel bootstrap, HTTP, DB or mutation.
$cssPath = $argv[1] ?? dirname(__DIR__).'/public/assets/css/ald1n-ui-v2.css';
$css = @file_get_contents($cssPath);
if ($css === false) {
    fwrite(STDERR, "FAIL: CSS file not readable: {$cssPath}\n");
    exit(2);
}
$css = preg_replace('~/\*[\s\S]*?\*/~', '', $css);
$selector = '.build16-catalog-shell .product-grid.unified-product-grid';
$rules = [];
$frames = [];
$start = 0;
$len = strlen($css);
for ($i = 0; $i < $len; ++$i) {
    $char = $css[$i];
    if ($char === '{') {
        $frames[] = ['header' => trim(substr($css, $start, $i - $start)), 'body_start' => $i + 1];
        $start = $i + 1;
        continue;
    }
    if ($char !== '}') {
        continue;
    }
    $frame = array_pop($frames);
    if ($frame === null) {
        fwrite(STDERR, "FAIL: Unbalanced CSS brace\n");
        exit(2);
    }
    $header = trim(preg_replace('/\s+/', ' ', $frame['header']));
    if ($header === $selector) {
        $body = substr($css, $frame['body_start'], $i - $frame['body_start']);
        if (preg_match('/\bgrid-template-columns\s*:\s*repeat\(\s*([0-9]+|auto-fill|auto-fit)\s*,/i', $body, $m)) {
            $maxWidth = null;
            foreach ($frames as $parent) {
                if (preg_match('/^@media\b/i', $parent['header']) && preg_match('/\bmax-width\s*:\s*(\d+)px/i', $parent['header'], $limit)) {
                    $maxWidth = (int) $limit[1];
                }
            }
            $rules[] = ['limit' => $maxWidth, 'columns' => $m[1]];
        }
    }
    $start = $i + 1;
}
if ($frames !== []) {
    fwrite(STDERR, "FAIL: Unclosed CSS block\n");
    exit(2);
}
$expected = [1700 => 4, 1500 => 4, 1499 => 3, 1300 => 3, 1101 => 3, 1100 => 2, 900 => 2, 681 => 2, 680 => 1, 480 => 1];
$passed = 0;
foreach ($expected as $width => $columns) {
    $actual = null;
    foreach ($rules as $rule) {
        if ($rule['limit'] === null || $width <= $rule['limit']) {
            $actual = is_numeric($rule['columns']) ? (int) $rule['columns'] : (string) $rule['columns'];
        }
    }
    $ok = $actual === $columns;
    echo ($ok ? 'PASS' : 'FAIL')." {$width}px: expected {$columns} columns, actual ".($actual ?? 'none').PHP_EOL;
    $passed += (int) $ok;
}
$onlyExpected = count($rules) === 4;
echo ($onlyExpected ? 'PASS' : 'FAIL').' canonical grid declarations: '.count($rules).'/4'.PHP_EOL;
$passed += (int) $onlyExpected;
echo sprintf('Catalog responsive grid: %d/11 successful.%s', $passed, PHP_EOL);
exit($passed === 11 ? 0 : 1);
