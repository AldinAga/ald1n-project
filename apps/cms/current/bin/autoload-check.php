#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$composer = json_decode((string) file_get_contents($root.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
$mapping = $composer['autoload']['psr-4']['App\\'] ?? null;
if ($mapping !== 'app/') {
    fwrite(STDERR, "FAIL Composer PSR-4 App\\ mapiranje mora biti app/.\n");
    exit(1);
}

$failed = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app', FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;
    $source = (string) file_get_contents($file->getPathname());
    if (!preg_match('/namespace\s+([^;]+);/', $source, $namespaceMatch)) {
        $failed[] = 'Nedostaje namespace: '.$file->getPathname();
        continue;
    }
    if (!preg_match('/\b(?:final\s+|abstract\s+)?(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/', $source, $classMatch)) {
        $failed[] = 'Nedostaje klasa/interfejs/trait/enum: '.$file->getPathname();
        continue;
    }
    $fqcn = trim($namespaceMatch[1]).'\\'.$classMatch[1];
    if (!str_starts_with($fqcn, 'App\\')) {
        $failed[] = 'Klasa nije u App namespace-u: '.$fqcn;
        continue;
    }
    $expected = $root.'/app/'.str_replace('\\', '/', substr($fqcn, 4)).'.php';
    if (realpath($expected) !== realpath($file->getPathname())) {
        $failed[] = sprintf('PSR-4 mismatch: %s -> %s', $fqcn, substr($file->getPathname(), strlen($root) + 1));
    }
}

foreach ($failed as $message) fwrite(STDERR, 'FAIL '.$message."\n");
fwrite(STDOUT, sprintf("Composer PSR-4 autoload provera: %d grešaka\n", count($failed)));
exit($failed === [] ? 0 : 1);
