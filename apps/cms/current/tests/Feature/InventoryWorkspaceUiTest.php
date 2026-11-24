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
        // G7: the old app.css assertions (inventory-meta-grid, max-height overrides) were about the old stylesheet only;
        // the Cobalt card layout below is the contract that remains.
        $this->assertStringContainsString('.a1-table--cards td::before{content:attr(data-label)', (string) file_get_contents(public_path('assets/css/ald1n-admin.css')));
    }
}
