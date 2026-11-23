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

/** Izvoz lagera ne sme da izvršava formule u Excelu/Sheets (ćelije koje počinju sa = + - @). */
final class InventoryCsvFormulaInjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_csv_prefixes_formula_cells_with_an_apostrophe(): void
    {
        $this->seed(CoreAccessSeeder::class);
        $super = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'), 'username' => 'csv-super',
            'email' => 'csv-super@test.local', 'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
        foreach ([['CSV-1', '=1+1'], ['CSV-2', '@SUM(1)'], ['CSV-3', '-2+3'], ['CSV-4', '+7'], ['CSV-5', 'Običan naziv']] as [$sku, $name]) {
            Product::query()->create([
                'sku' => $sku, 'name' => $name, 'slug' => strtolower($sku), 'price_amount' => 1, 'price_currency' => 'EUR',
                'description' => 'o', 'stock_quantity' => 1, 'low_stock_threshold' => 1, 'status' => 'active',
                'created_by' => $super->id, 'updated_by' => $super->id,
            ]);
        }

        $csv = $this->actingAs($super)->get(route('admin.inventory.csv'))->assertOk()->getContent();

        foreach (['=1+1', '@SUM(1)', '-2+3', '+7'] as $formula) {
            self::assertStringContainsString(";'".$formula.';', $csv);
            self::assertStringNotContainsString(';'.$formula.';', $csv);
        }
        self::assertStringContainsString('Običan naziv', $csv);
        self::assertStringNotContainsString("'Običan", $csv);
    }
}
