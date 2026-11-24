#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
// G7 PR B: variables are defined by the Cobalt stylesheets now, not by the old app.css.
$files = array_merge(
    [$root.'/public/assets/css/ald1n-tokens.css', $root.'/public/assets/css/ald1n-admin.css', $root.'/public/assets/css/ald1n-auth.css', $root.'/public/assets/css/ald1n-icons.css'],
    glob($root.'/public/assets/css/ald1n-admin-*.css') ?: [],
);
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/resources/views')) as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $files[] = $file->getPathname();
    }
}

$definitions = [];
$references = [];
foreach ($files as $file) {
    $content = (string) file_get_contents($file);
    preg_match_all('/(--[A-Za-z0-9_-]+)\s*:/', $content, $definedMatches);
    foreach ($definedMatches[1] as $name) {
        $definitions[$name] = true;
    }

    // Every reference counts, with or without a fallback, except variables that JavaScript sets on purpose.
    preg_match_all('/var\(\s*(--[A-Za-z0-9_-]+)/', $content, $referenceMatches);
    foreach ($referenceMatches[1] as $name) {
        if (in_array($name, ['--a1-btt-lift'], true)) { // set by layouts/partials/back-to-top.blade.php via style.setProperty
            continue;
        }
        $references[$name][$file] = true;
    }
}

$missing = array_values(array_diff(array_keys($references), array_keys($definitions)));
sort($missing);

$management = (string) file_get_contents($root.'/resources/views/admin/reports/management.blade.php');
// The checks that read old app.css / ald1n-ui-v2.css (management panel colours, legacy alias mapping) were removed in G7 PR B.
$checks = [
    'Sve korišćene CSS promenljive imaju definiciju' => $missing === [],
    'Management view nema legacy panel/border promenljive' => !str_contains($management, '--panel-bg') && !str_contains($management, '--border-color'),
];

$failed = 0;
foreach ($checks as $label => $ok) {
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
    if (!$ok) {
        $failed++;
    }
}

if ($missing !== []) {
    fwrite(STDERR, 'Nedefinisane promenljive: '.implode(', ', $missing).PHP_EOL);
    foreach ($missing as $name) {
        fwrite(STDERR, '  '.$name.': '.implode(', ', array_keys($references[$name])).PHP_EOL);
    }
}

fwrite(STDOUT, sprintf("Theme CSS smoke: %d/%d uspešno.\n", count($checks) - $failed, count($checks)));
exit($failed === 0 ? 0 : 1);
