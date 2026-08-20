#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$files = [$root.'/public/assets/css/app.css'];
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

    preg_match_all('/var\(\s*(--[A-Za-z0-9_-]+)/', $content, $referenceMatches);
    foreach ($referenceMatches[1] as $name) {
        $references[$name][$file] = true;
    }
}

$missing = array_values(array_diff(array_keys($references), array_keys($definitions)));
sort($missing);

$management = (string) file_get_contents($root.'/resources/views/admin/reports/management.blade.php');
$css = (string) file_get_contents($root.'/public/assets/css/app.css');
/* CMS_UX_MAXIMAL_PHASE7_THEME_SMOKE_SHARED_AUTHORITY_V1 */
$phase7ThemeCssAuthority = (string) file_get_contents(
    $root.'/public/assets/css/ald1n-ui-v2.css',
);
$checks = [
    'Sve korišćene CSS promenljive imaju definiciju' => $missing === [],
    'Management kartice koriste aktivni panel i border' => str_contains($phase7ThemeCssAuthority, 'background:var(--panel)') && str_contains($phase7ThemeCssAuthority, 'border:1px solid var(--line)'),
    'Management view nema legacy panel/border promenljive' => !str_contains($management, '--panel-bg') && !str_contains($management, '--border-color'),
    'Management progress bar prati temu' => str_contains($phase7ThemeCssAuthority, 'background:var(--panel-2)') && str_contains($phase7ThemeCssAuthority, 'background:var(--primary)'),
    'Legacy CSS aliasi mapirani su na aktivnu temu' => str_contains($css, '--accent:var(--primary)')
        && str_contains($css, '--border:var(--line)')
        && str_contains($css, '--panel-bg:var(--panel)')
        && str_contains($css, '--surface-2:var(--panel-2)')
        && str_contains($css, '--surface-soft:var(--panel-2)'),
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
