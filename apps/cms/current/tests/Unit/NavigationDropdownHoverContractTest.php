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
        $css = (string) file_get_contents($root.'/public/assets/css/app.css');

        self::assertStringContainsString('hoverCloseTimers', $layout);
        self::assertStringContainsString('clickPinnedDropdowns', $layout);
        self::assertStringContainsString('scheduleHoverClose', $layout);
        self::assertStringContainsString('menu?.addEventListener(\'mouseenter\'', $layout);
        self::assertStringContainsString('.nav-dropdown-menu:before', $css);
        self::assertStringContainsString('top:-12px', $css);
    }
}
