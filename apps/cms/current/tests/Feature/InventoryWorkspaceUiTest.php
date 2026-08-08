<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class InventoryWorkspaceUiTest extends TestCase
{
    public function test_inventory_workspace_uses_single_active_operation_and_preserves_filters(): void
    {
        $view = (string) file_get_contents(resource_path('views/admin/inventory/index.blade.php'));
        $controller = (string) file_get_contents(app_path('Http/Controllers/Admin/InventoryController.php'));

        $this->assertStringContainsString('inventory-operation-tabs', $view);
        $this->assertStringContainsString("\$mode === 'receive'", $view);
        $this->assertStringContainsString("\$mode === 'count'", $view);
        $this->assertStringContainsString('_return_q', $view);
        $this->assertStringContainsString('PAGE_LIMITS', $controller);
        $this->assertStringContainsString('matchingProductCount', $controller);
    }

    public function test_inventory_css_contains_no_vertical_table_scroll_and_mobile_card_layout(): void
    {
        $css = (string) file_get_contents(public_path('assets/css/app.css'));

        $this->assertStringContainsString('v2.1.0-beta5 - inventory workspace responsive redesign', $css);
        $this->assertStringContainsString('max-height:none!important', $css);
        $this->assertStringContainsString('overflow-y:visible!important', $css);
        $this->assertStringContainsString('.inventory-meta-grid', $css);
        $this->assertStringContainsString('.inventory-data-table td{display:grid!important', $css);
    }
}
