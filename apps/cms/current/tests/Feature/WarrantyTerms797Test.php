<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\Role;
use App\Models\User;
use App\Models\WarrantyRule;
use App\Services\DirectSaleService;
use App\Services\Pdf\WarrantyCertificatePayloadService;
use App\Services\SettingsService;
use App\Services\WarrantyService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class WarrantyTerms797Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp(); $this->seed(CoreAccessSeeder::class); Storage::fake('local');
        WarrantyRule::query()->where('scope_type', 'global')->update(['terms' => null, 'is_active' => true, 'duration_months' => 0, 'duration_days' => 10]);
    }

    private function operator(): User
    {
        return User::query()->create(['role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'), 'username' => 'pdf797-operator', 'email' => 'pdf797@example.test', 'first_name' => 'Synthetic', 'password_hash' => Hash::make('Secret123!'), 'status' => 'active']);
    }

    private function sale(User $actor): \App\Models\Order
    {
        $product = Product::query()->create(['sku' => 'PDF797-ITEM', 'name' => 'Synthetic product', 'slug' => 'pdf797-item', 'price_amount' => 12000, 'price_currency' => 'RSD', 'description' => 'QA only.', 'stock_quantity' => 10, 'low_stock_threshold' => 1, 'status' => 'active', 'created_by' => $actor->id]);
        return app(DirectSaleService::class)->record($product, $actor, ['buyer_name' => 'Synthetic Buyer', 'buyer_phone' => '060000000', 'quantity' => 1, 'sale_price_rsd' => 12000, 'payment_method' => 'cash'], 'pdf797-sale');
    }

    public function test_future_warranty_from_blank_rule_snapshots_cause_based_terms_and_remains_idempotent(): void
    {
        $actor = $this->operator(); $order = $this->sale($actor);
        $warranty = ProductWarranty::query()->sole();
        self::assertStringContainsString('pada, udara', (string) $warranty->terms_snapshot);
        self::assertStringContainsString('zakonska prava', (string) $warranty->terms_snapshot);
        self::assertSame(10, $warranty->duration_days);
        app(WarrantyService::class)->ensureForOrder($order, $actor);
        self::assertSame(1, ProductWarranty::query()->count());
        self::assertSame($warranty->terms_snapshot, $warranty->fresh()->terms_snapshot);
    }

    public function test_existing_issued_snapshot_and_dates_are_not_rewritten(): void
    {
        $actor = $this->operator(); $order = $this->sale($actor);
        $warranty = ProductWarranty::query()->sole(); $warranty->update(['terms_snapshot' => null]);
        $before = $warranty->fresh()->only(['terms_snapshot', 'starts_at', 'expires_at', 'warranty_number']);
        app(WarrantyService::class)->ensureForOrder($order, $actor);
        self::assertEquals($before, $warranty->fresh()->only(array_keys($before)));
    }

    public function test_existing_custom_rule_text_is_preserved_exactly(): void
    {
        $text = "Poseban ranije definisan uslov.\n\nDrugi pasus.";
        WarrantyRule::query()->where('scope_type', 'global')->update(['terms' => $text]);
        $actor = $this->operator(); $this->sale($actor);
        self::assertSame($text, ProductWarranty::query()->sole()->terms_snapshot);
    }

    public function test_rule_form_shows_default_but_does_not_rewrite_database_on_get(): void
    {
        $actor = $this->operator();
        $this->actingAs($actor)->get('/admin/warranties')->assertOk()->assertSee('pada, udara');
        self::assertNull(WarrantyRule::query()->where('scope_type', 'global')->firstOrFail()->terms);
    }

    public function test_updating_other_rule_fields_does_not_replace_custom_terms(): void
    {
        $actor = $this->operator(); $rule = WarrantyRule::query()->where('scope_type', 'global')->firstOrFail();
        $rule->update(['terms' => 'Existing explicit promise.']);
        app(\App\Services\WarrantyAdminService::class)->updateRule($rule, $actor, ['name' => 'Renamed QA rule', 'scope_type' => 'global', 'duration_months' => 0, 'duration_days' => 10, 'is_active' => true]);
        self::assertSame('Existing explicit promise.', $rule->fresh()->terms);
    }

    public function test_certificate_payload_uses_real_configured_issuer_contact(): void
    {
        $actor = $this->operator(); $this->sale($actor);
        app(SettingsService::class)->putMany(['documents_company_address' => 'Synthetic Street 1', 'documents_company_city' => 'Test City', 'documents_company_phone' => '060000001', 'documents_company_email' => 'support@example.test'], $actor->id);
        $payload = app(WarrantyCertificatePayloadService::class)->build(ProductWarranty::query()->sole());
        self::assertSame('Synthetic Street 1', $payload['company_address'] ?? null);
        self::assertSame('support@example.test', $payload['company_email'] ?? null);
    }
}
