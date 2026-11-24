<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * <x-icon> renders .a1-icon (ald1n-icons.css), never the old .ui-icon of app.css and ald1n-ui-v2.css. Every size
 * passed in a view needs its .a1-icon--n{size} rule, because ald1n-admin.css sizes .a1-icon from a variable and
 * would otherwise override the size attribute. Author: Ald1n <https://ald1n.com>
 */
final class IconClassesContractTest extends TestCase
{
    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    public function test_every_icon_size_used_in_views_has_a_rule(): void
    {
        $css = (string) file_get_contents($this->root().'/public/assets/css/ald1n-icons.css');
        $sizes = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root().'/resources/views', RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (!str_ends_with((string) $file, '.php')) {
                continue;
            }
            preg_match_all('~<x-icon\b[^>]*?\s:?size="(\d+)"~', (string) file_get_contents((string) $file), $m);
            array_push($sizes, ...$m[1]);
        }

        self::assertNotSame([], $sizes, 'No <x-icon size> found; the scan is broken.');
        $sizes[] = '18'; // component default
        foreach (array_unique($sizes) as $size) {
            self::assertStringContainsString(".a1-icon.a1-icon--n{$size}{width:{$size}px;height:{$size}px}", $css, "size {$size}");
        }
    }

    public function test_layout_and_icon_component_do_not_use_old_icon_classes(): void
    {
        foreach (['components/icon', 'layouts/guest'] as $view) {
            $source = (string) file_get_contents($this->root()."/resources/views/{$view}.blade.php");
            self::assertStringNotContainsString('ui-icon', $source, $view);
            self::assertStringNotContainsString('icon-button', $source, $view);
        }
        foreach (['layouts/app', 'layouts/guest'] as $layout) {
            self::assertStringContainsString('assets/css/ald1n-icons.css', (string) file_get_contents($this->root()."/resources/views/{$layout}.blade.php"), $layout);
        }
    }
}
