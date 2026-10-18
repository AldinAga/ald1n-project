<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ProductEditorLiquidGlassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_create_editor_has_compact_sticky_primary_and_unchanged_draft_submission(): void
    {
        $html = $this->actingAs($this->editor('superadmin'))->get(route('admin.products.create'))
            ->assertOk()->getContent();
        self::assertIsString($html);
        self::assertStringContainsString('class="liquid-glass-product-editor-form"', $html);
        self::assertStringContainsString('data-ux-sticky-actions', $html);
        self::assertStringContainsString('action="'.route('admin.products.store').'"', $html);

        $bar = $this->stickyBar($html);
        self::assertStringContainsString('product-editor-action-secondary', $bar);
        self::assertStringContainsString('product-editor-action-buttons', $bar);
        self::assertStringContainsString('class="button button-primary product-editor-action-primary"', $bar);
        self::assertStringContainsString('data-product-save-primary', $bar);
        self::assertStringContainsString('Kreiraj artikal', $bar);
        self::assertStringContainsString('name="save_draft" value="1" formnovalidate', $bar);
        self::assertSame(1, substr_count($bar, 'data-product-save-draft'));
        self::assertSame(1, substr_count($bar, 'data-product-save-primary'));
        self::assertStringNotContainsString('button-large', $bar);
        self::assertStringNotContainsString('product-editor-action-direct', $bar);
    }

    public function test_active_superadmin_edit_keeps_direct_sale_link_and_primary_edit_submit(): void
    {
        $admin = $this->editor('superadmin');
        $product = $this->product($admin, 'active');
        $html = $this->actingAs($admin)->get(route('admin.products.edit', $product))
            ->assertOk()->getContent();
        self::assertIsString($html);
        self::assertStringContainsString('action="'.route('admin.products.update', $product).'"', $html);
        self::assertStringContainsString('name="_method" value="PUT"', $html);

        $bar = $this->stickyBar($html);
        self::assertStringContainsString('Sačuvaj izmene', $bar);
        self::assertStringContainsString('class="button button-primary product-editor-action-primary"', $bar);
        self::assertStringContainsString('class="button button-secondary product-editor-action-draft"', $bar);
        self::assertStringContainsString('name="save_draft" value="1" formnovalidate', $bar);
        self::assertStringContainsString('class="button button-ghost product-editor-action-direct"', $bar);
        self::assertStringContainsString('href="'.route('catalog.show', ['slug' => $product->slug]).'#direct-sale-title"', $bar);
        self::assertStringNotContainsString('button-large', $bar);
    }

    public function test_non_superadmin_edit_does_not_show_direct_sale_action(): void
    {
        $admin = $this->editor('admin');
        $product = $this->product($admin, 'inactive');
        $html = $this->actingAs($admin)->get(route('admin.products.edit', $product))
            ->assertOk()->getContent();
        self::assertIsString($html);
        $bar = $this->stickyBar($html);
        self::assertStringContainsString('Sačuvaj izmene', $bar);
        self::assertStringContainsString('Sačuvaj kao nacrt', $bar);
        self::assertStringNotContainsString('product-editor-action-direct', $bar);
        self::assertStringNotContainsString('#direct-sale-title', $bar);
    }

    private function stickyBar(string $html): string
    {
        $start = strpos($html, '<div class="product-editor-action-bar"');
        self::assertNotFalse($start);
        $end = strpos($html, '</form>', $start);
        self::assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    private function editor(string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => 'liquid-editor-'.$role,
            'email' => 'liquid-editor-'.$role.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'first_name' => 'Editor',
            'status' => 'active',
        ]);
    }

    private function product(User $owner, string $status): Product
    {
        return Product::query()->create([
            'sku' => 'LG-PRODUCT-'.strtoupper($status),
            'name' => 'Liquid Glass Test Artikal',
            'description' => 'Testni artikal za isključivo izolovanu proveru izgleda forme.',
            'slug' => 'liquid-glass-test-'.strtolower($status),
            'status' => $status,
            'price_amount' => 250,
            'price_currency' => 'EUR',
            'stock_quantity' => 5,
            'low_stock_threshold' => 1,
            'created_by' => $owner->id,
        ]);
    }
}
