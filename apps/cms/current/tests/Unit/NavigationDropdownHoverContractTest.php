<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class NavigationDropdownHoverContractTest extends TestCase
{
    public function test_desktop_dropdown_remains_reachable_between_summary_and_menu(): void
    {
        $root = dirname(__DIR__, 2);
        $layout = (string) file_get_contents($root.'/resources/views/layouts/app.blade.php');
        $css = (string) file_get_contents($root.'/public/assets/css/ald1n-admin.css');

        self::assertStringContainsString('hoverCloseTimers', $layout);
        self::assertStringContainsString('clickPinnedDropdowns', $layout);
        self::assertStringContainsString('scheduleHoverClose', $layout);
        self::assertStringContainsString('menu?.addEventListener(\'mouseenter\'', $layout);
        // The new shell panel opens 8px below its summary; a pseudo element bridges that gap for the pointer.
        self::assertStringContainsString('.a1-nav-dd[open]::after{content:"";position:absolute;left:0;right:0;top:100%;height:var(--a1-space-8)}', $css);
        // A click on a hover-opened summary closes the menu instead of pinning it.
        self::assertStringContainsString('const wasOpen = dropdown.open;', $layout);
    }
}
