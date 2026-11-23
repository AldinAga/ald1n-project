<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Kobalt G5: users, access groups, customer portal, portal messages, warranties, inventory, stock and audit pages are
 * built from a1-* components only. No inline style, no <style> block, no hard-coded colour, none of the old class
 * families, and the page stylesheet uses tokens only. Presentation contract: names, routes and data hooks are tested
 * by the feature tests of each module. Author: Ald1n <https://ald1n.com>
 */
final class G5PagesCobaltContractTest extends TestCase
{
    private const VIEWS = [
        'admin/users/index',
        'admin/users/retirement-preview',
        'admin/user-groups/index',
        'admin/customer-portal/index',
        'admin/customer-portal/show',
        'admin/customer-portal/conversation',
        'portal/messages/index',
        'portal/messages/show',
        'admin/warranties/index',
        'admin/warranties/show',
        'warranties/index',
        'warranties/show',
        'admin/inventory/index',
        'admin/stock/index',
        'admin/audit/index',
    ];

    /** Class tokens of the old design system; none may remain in these pages. */
    private const OLD_CLASSES = '/class="(?:[^"]*\s)?(?:button(?:-[a-z]+)*|panel|page-heading|status-badge|status-[a-z]+|form-section|form-grid|filter-panel|filter-actions|search-field|stat-card|stats-grid|detail-list|settings-grid|table-wrap|admin-table(?:-wrap)?|flat-table|section-heading-row|card-header-row|header-button-row|count-pill|eyebrow|muted|empty-state|empty-inline|alert(?: [a-z-]+)?|stack-form|checkbox-row|check-row|full-width|text-danger|text-success|report-summary-[a-z-]+|metric-[a-z-]+|access-control-[a-z-]+|portal-[a-z-]+|conversation-[a-z-]+|customer-portal-[a-z-]+|session-[a-z-]+|order-link-[a-z-]+|inventory-[a-z-]+|audit-[a-z-]+|timeline-item|details-popover|ux-stock-[a-z0-9-]+)(?:\s[^"]*)?"/';

    /** @return array<string, array{string}> */
    public static function views(): array
    {
        $cases = [];
        foreach (self::VIEWS as $view) {
            $cases[$view] = [$view];
        }

        return $cases;
    }

    private static function source(string $view): string
    {
        return (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/'.$view.'.blade.php');
    }

    #[DataProvider('views')]
    public function test_page_uses_kobalt_components_only(string $view): void
    {
        $source = self::source($view);

        self::assertNotSame('', $source, $view);
        self::assertStringContainsString('class="a1-ops a1-g5', $source, $view.' must sit in the a1-ops wrapper.');
        self::assertStringContainsString("@include('admin.partials.g5-styles')", $source, $view.' must load the G5 stylesheet.');
        self::assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $source, $view.' has an inline style.');
        self::assertDoesNotMatchRegularExpression('/<style[\s>]/i', $source, $view.' has a <style> block.');
        self::assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $source, $view.' has a hard-coded colour.');
        self::assertDoesNotMatchRegularExpression('/\b(?:rgba?|hsla?)\(/i', $source, $view.' has a hard-coded colour function.');
        self::assertSame(0, preg_match(self::OLD_CLASSES, $source, $m), $view.' still uses an old class: '.($m[0] ?? ''));
        self::assertSame(1, substr_count($source, '<h1 '), $view.' needs exactly one h1.');
    }

    public function test_the_retired_helpers_are_gone(): void
    {
        $root = dirname(__DIR__, 2);

        self::assertFileDoesNotExist($root.'/resources/views/admin/access-control/partials/workspace-style.blade.php');
        self::assertFileDoesNotExist($root.'/public/assets/css/ald1n-user-retirement-action.css');
    }

    public function test_page_stylesheet_uses_tokens_only(): void
    {
        $css = (string) file_get_contents(dirname(__DIR__, 2).'/public/assets/css/ald1n-admin-g5.css');
        $css = (string) preg_replace('~/\*.*?\*/~s', '', $css);

        self::assertNotSame('', trim($css));
        self::assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $css, 'Hard-coded hex colour.');
        self::assertDoesNotMatchRegularExpression('/\b(?:rgba?|hsla?)\(/i', $css, 'Hard-coded colour function.');
        // The single !important is the [hidden] guard.
        self::assertSame(1, substr_count($css, '!important'));
        self::assertStringContainsString('.a1-g5 [hidden]{display:none!important}', $css);
    }
}
