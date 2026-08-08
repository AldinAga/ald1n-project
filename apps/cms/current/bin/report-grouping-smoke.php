#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$service = (string) file_get_contents($root.'/app/Services/ManagementReportService.php');
$doctor = (string) file_get_contents($root.'/app/Console/Commands/ManagementReportsDoctorCommand.php');

$checks = [
    'Segmenti koriste izvedenu tabelu' => str_contains($service, "fromSub(\$segmentRows, 'segment_rows')"),
    'Segmenti grupišu prostu kolonu' => str_contains($service, "->groupBy('segment_label')"),
    'Složeni label izraz se ne grupiše direktno' => !str_contains($service, '->groupByRaw($label)'),
    'Trend koristi izvedenu tabelu' => str_contains($service, "fromSub(\$trendRows, 'trend_rows')"),
    'Trend grupiše report_period' => str_contains($service, "->groupBy('report_period')"),
    'Doctor i dalje testira pravi PDF render' => str_contains($doctor, "\$reports->pdf(\$actor"),
];

$failed = 0;
foreach ($checks as $label => $ok) {
    fwrite($ok ? STDOUT : STDERR, ($ok ? 'PASS ' : 'FAIL ').$label.PHP_EOL);
    if (!$ok) $failed++;
}
fwrite(STDOUT, sprintf("Report grouping smoke: %d/%d uspešno.\n", count($checks)-$failed, count($checks)));
exit($failed === 0 ? 0 : 1);
