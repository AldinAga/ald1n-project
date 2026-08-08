#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$directories = ['app', 'bootstrap', 'config', 'database', 'routes', 'tests', 'bin'];
$failed = 0;
$total = 0;

foreach ($directories as $directory) {
    $path = $root.'/'.$directory;
    if (!is_dir($path)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php' || $file->getFilename() === 'php-lint.php') continue;
        $total++;
        $command = escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file->getPathname()).' 2>&1';
        exec($command, $output, $code);
        $text = implode("\n", $output);
        $hasDiagnostic = preg_match('/\bPHP (Warning|Deprecated|Notice|Parse error|Fatal error):/i', $text) === 1;
        if ($code !== 0 || $hasDiagnostic) {
            $failed++;
            fwrite(STDERR, $text."\n");
        }
        $output = [];
    }
}

fwrite(STDOUT, sprintf("PHP lint: %d fajlova, greške: %d\n", $total, $failed));
exit($failed === 0 ? 0 : 1);
