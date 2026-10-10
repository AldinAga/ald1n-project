#!/usr/bin/env php
<?php
declare(strict_types=1);

// ALD1N_CMS_LAYOUT_STANDARDS_MODE_CONTRACT
// Source-only guard; does not bootstrap Laravel, read .env or touch a database.
$root = dirname(__DIR__);
$layout = @file_get_contents($root.'/resources/views/layouts/app.blade.php');
if ($layout === false) {
    fwrite(STDERR, "FAIL: Layout file missing\n");
    exit(2);
}
$doctype = stripos($layout, '<!doctype html>');
$html = stripos($layout, '<html');
$head = stripos($layout, '<head>');
$headEnd = stripos($layout, '</head>');
$firstStyle = stripos($layout, '<style');
$moduleStyle = strpos($layout, '[data-module-visibility="0"]');
$appLink = strpos($layout, "asset('assets/css/app.css')");
$uxLink = strpos($layout, "asset('assets/css/ald1n-ui-v2.css')");
$stackStyles = strpos($layout, "@stack('styles')");
$checks = [
    'DOCTYPE exists' => $doctype !== false,
    'No emitted style before DOCTYPE' => $doctype !== false && ($firstStyle === false || $firstStyle > $doctype),
    'HTML and head follow DOCTYPE' => $doctype !== false && $html !== false && $head !== false && $doctype < $html && $html < $head,
    'Module visibility style remains in head' => $moduleStyle !== false && $head !== false && $headEnd !== false && $head < $moduleStyle && $moduleStyle < $headEnd,
    'Both CSS assets stay in head in original order' => $appLink !== false && $uxLink !== false && $head !== false && $headEnd !== false && $head < $appLink && $appLink < $uxLink && $uxLink < $headEnd,
    'Stacked styles remain after CSS and before closing head' => $stackStyles !== false && $uxLink !== false && $headEnd !== false && $uxLink < $stackStyles && $stackStyles < $headEnd,
    'Theme initialization preserved' => str_contains($layout, 'document.documentElement.dataset.theme = resolved;'),
];
$passed = 0;
foreach ($checks as $label => $ok) {
    if ($ok) ++$passed;
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
}
fwrite(STDOUT, sprintf("Layout standards smoke: %d/%d successful.\n", $passed, count($checks)));
exit($passed === count($checks) ? 0 : 1);
