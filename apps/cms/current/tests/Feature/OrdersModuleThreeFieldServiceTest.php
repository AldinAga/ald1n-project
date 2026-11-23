<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AfterSalesAction;
use App\Models\AfterSalesCase;
use App\Models\AfterSalesCaseItem;
use App\Models\FieldServiceTeam;
use App\Models\FieldWorkOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\ServicePart;
use App\Models\ServicePartPurchaseRequest;
use App\Models\ServicePartSupplier;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Module 3 (orders on Kobalt components): field operations and service parts pages.
 * What each role sees and can reach, with real rows, and the markup contract.
 * Author: Ald1n <https://ald1n.com>
 */
final class OrdersModuleThreeFieldServiceTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_CLASSES = 'panel|button(?:-[a-z]+)?|page-heading|status-badge|filter-panel|filter-grid|form-grid|form-section|settings-grid|detail-list|table-wrap|stat-card|stats-grid|back-link|alpha-note|checkbox-field|checkbox-row|legacy-metric-card|header-button-row|section-heading-row|danger-zone|form-side|operational-order-grid|order-main-column|field-[a-z-]+|service-parts-grid|purchase-item-row|details-popover|row-warning|muted|eyebrow';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_calendar_shows_the_work_order_to_admin_and_superadmin_with_the_new_markup(): void
    {
        [$admin, $workOrder] = $this->workOrderScenario();
        $start = now()->startOfDay()->addHours(10);
        $workOrder->forceFill(['planned_start_at' => $start, 'planned_end_at' => $start->copy()->addHours(2)])->save();
        $range = '?from='.now()->format('Y-m-d').'&to='.now()->addDays(6)->format('Y-m-d');

        foreach ([$admin, $this->person('superadmin', 'cal')] as $viewer) {
            $html = $this->root((string) $this->actingAs($viewer)->get('/admin/field-operations'.$range)->assertOk()->getContent());

            self::assertSame(1, substr_count($html, '<h1 '));
            self::assertStringContainsString($workOrder->work_order_number, $html);
            self::assertStringContainsString('a1-ow-cal', $html);
            self::assertStringContainsString('10:00–12:00', $html);
            self::assertMatchesRegularExpression('/a1-pill a1-pill--warning">Planiran</', $html);
            self::assertSame(4, substr_count($html, 'class="a1-kpi a1-kpi--link"'));
            self::assertStringContainsString('href="'.route('admin.field-service-teams.index').'"', $html);
            self::assertStringContainsString('href="'.route('admin.field-operations.show', $workOrder).'"', $html);
            self::assertStringContainsString('role="search"', $html);
            self::assertSame(1, substr_count($html, 'a1-btn a1-btn--primary'));
            self::assertSame(0, preg_match('/class="(?:[^"]*\s)?(?:'.self::OLD_CLASSES.')(?:\s[^"]*)?"/', $html), 'Old classes must be gone.');
        }
    }

    public function test_unscheduled_view_lists_work_orders_in_a_labelled_card_table(): void
    {
        [$admin, $workOrder] = $this->workOrderScenario();
        $workOrder->forceFill(['planned_start_at' => null, 'planned_end_at' => null])->save();

        $html = $this->root((string) $this->actingAs($admin)->get('/admin/field-operations?unscheduled=1')->assertOk()->getContent());

        self::assertStringContainsString('Neraspoređeni radni nalozi', $html);
        self::assertStringContainsString('a1-table a1-table--cards', $html);
        self::assertStringContainsString('aria-label="Otvori radni nalog '.$workOrder->work_order_number.'"', $html);
        self::assertStringContainsString('data-label="Status"><span class="a1-pill a1-pill--warning">Planiran</span>', $html);
        self::assertMatchesRegularExpression('/name="unscheduled" value="1" checked/', $html);
        self::assertStringNotContainsString('a1-ow-cal', $html);
    }

    public function test_work_order_detail_has_one_primary_action_csrf_methods_and_confirmations(): void
    {
        [$admin, $workOrder] = $this->workOrderScenario();
        ServicePart::query()->create([
            'sku' => 'M3-DET-1', 'name' => 'Detalj deo', 'unit' => 'kom', 'stock_quantity' => 4, 'reserved_quantity' => 0,
            'minimum_quantity' => 1, 'average_cost_rsd' => 100, 'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $part = ServicePart::query()->where('sku', 'M3-DET-1')->sole();
        $this->actingAs($admin)->post('/admin/field-operations/'.$workOrder->id.'/parts', [
            'service_part_id' => $part->id, 'requested_quantity' => 2, 'supply_mode' => 'local_stock',
        ])->assertRedirect()->assertSessionHas('status');
        $lineId = $workOrder->parts()->sole()->id;

        foreach ([$admin, $this->person('superadmin', 'det')] as $viewer) {
            $html = $this->root((string) $this->actingAs($viewer)->get('/admin/field-operations/'.$workOrder->id)->assertOk()->getContent());

            self::assertSame(1, substr_count($html, '<h1 '));
            self::assertStringContainsString('<h1 class="a1-title">'.$workOrder->work_order_number.'</h1>', $html);
            self::assertStringContainsString('aria-label="Nazad na vrh"', $html);
            self::assertMatchesRegularExpression('/a1-pill a1-pill--warning">Planiran</', $html);
            // A planned order: "crew left" is the one primary action, the other transition is secondary.
            self::assertSame(1, substr_count($html, 'a1-btn a1-btn--primary'));
            self::assertMatchesRegularExpression('/<button class="a1-btn a1-btn--primary" type="submit">.{0,900}Ekipa je krenula/s', $html);
            self::assertSame(1, substr_count($html, 'aria-current="step"'));
            self::assertSame(0, preg_match('/class="(?:[^"]*\s)?(?:'.self::OLD_CLASSES.')(?:\s[^"]*)?"/', $html), 'Old classes must be gone.');

            $forms = $this->forms($html);
            self::assertGreaterThanOrEqual(7, count($forms));
            foreach ($forms as $form) {
                self::assertStringContainsString('name="_token"', $form, 'Every POST form carries the CSRF token.');
            }
            self::assertSame(1, $this->countForms($forms, '/field-operations/'.$workOrder->id.'/schedule', 'name="_method" value="PATCH"'));
            self::assertSame(1, $this->countForms($forms, '/field-operations/'.$workOrder->id.'/en-route'));
            self::assertSame(1, $this->countForms($forms, '/field-operations/'.$workOrder->id.'/on-site'));
            self::assertSame(1, $this->countForms($forms, '/field-operations/'.$workOrder->id.'/parts/reserve'));
            self::assertSame(1, $this->countForms($forms, '/field-operations/'.$workOrder->id.'/complete', 'enctype="multipart/form-data"'));
            self::assertSame(1, $this->countForms($forms, '/field-operations/'.$workOrder->id.'/cancel', 'data-confirm="Otkazati radni nalog i povezanu postprodajnu radnju?"'));
            self::assertSame(1, $this->countForms($forms, '/field-operations/'.$workOrder->id.'/parts/'.$lineId.'"', 'name="_method" value="DELETE"'));
            self::assertStringContainsString('data-confirm="Ukloniti deo i osloboditi rezervaciju?"', $html);
            self::assertStringContainsString('aria-label="Ukloni deo Detalj deo sa radnog naloga '.$workOrder->work_order_number.'"', $html);
            self::assertStringContainsString('name="attachments[]"', $html);
            // Part line in the table: quantities with the Serbian number format.
            self::assertStringContainsString('data-label="Traženo">2,00 kom', $html);
            self::assertStringContainsString('a1-table__sub">M3-DET-1<', $html);
        }
    }

    public function test_view_only_role_reaches_only_the_lists_and_never_the_manage_or_procurement_routes(): void
    {
        [$admin, $workOrder] = $this->workOrderScenario();
        ServicePart::query()->create([
            'sku' => 'M3-VIEW-1', 'name' => 'Pregledni deo', 'unit' => 'kom', 'stock_quantity' => 3, 'reserved_quantity' => 0,
            'minimum_quantity' => 1, 'average_cost_rsd' => 100, 'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $viewer = $this->groupPerson('m3-viewer', ['field_operations.view', 'service_parts.view']);

        $fieldHtml = $this->root((string) $this->actingAs($viewer)->get('/admin/field-operations?from='.now()->subDay()->format('Y-m-d').'&to='.now()->addDays(5)->format('Y-m-d'))->assertOk()->getContent());
        self::assertSame(1, substr_count($fieldHtml, '<h1 '));
        self::assertStringContainsString('Terenske operacije', $fieldHtml);
        self::assertStringContainsString('a1-ow-cal', $fieldHtml);

        $partsHtml = $this->root((string) $this->actingAs($viewer)->get('/admin/service-parts')->assertOk()->getContent());
        self::assertStringContainsString('Pregledni deo', $partsHtml);
        self::assertStringContainsString('M3-VIEW-1', $partsHtml);
        // The procurement links are gated by service_parts.procurement.
        self::assertStringNotContainsString(route('admin.service-part-purchases.index'), $partsHtml);
        self::assertStringNotContainsString(route('admin.service-part-suppliers.index'), $partsHtml);

        // Manage and procurement routes stay closed for this role.
        $this->actingAs($viewer)->get('/admin/service-part-purchases')->assertForbidden();
        $this->actingAs($viewer)->get('/admin/field-service-teams')->assertForbidden();
        $this->actingAs($viewer)->patch('/admin/field-operations/'.$workOrder->id.'/schedule', [])->assertForbidden();
        $this->actingAs($viewer)->post('/admin/service-parts', ['sku' => 'X', 'name' => 'X', 'unit' => 'kom'])->assertForbidden();
        // The detail page needs after-sales management on top of the view permission.
        $this->actingAs($viewer)->get('/admin/field-operations/'.$workOrder->id)->assertNotFound();
    }

    public function test_role_without_access_gets_403_on_every_page(): void
    {
        [, $workOrder] = $this->workOrderScenario();
        $stranger = $this->person('user', 'none');

        foreach (['/admin/field-operations', '/admin/field-operations/'.$workOrder->id, '/admin/field-service-teams', '/admin/service-parts', '/admin/service-part-purchases'] as $url) {
            $this->actingAs($stranger)->get($url)->assertForbidden();
        }
    }

    public function test_service_parts_page_shows_stock_money_row_actions_and_forms(): void
    {
        $admin = $this->person('admin', 'parts');
        $supplier = ServicePartSupplier::query()->create([
            'code' => 'DOB-M3', 'name' => 'Dobavljač M3', 'lead_time_days' => 2, 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        ServicePart::query()->create([
            'sku' => 'M3-OK-1', 'name' => 'Deo na stanju', 'unit' => 'kom', 'stock_quantity' => 5, 'reserved_quantity' => 1,
            'minimum_quantity' => 1, 'average_cost_rsd' => 1000, 'preferred_supplier_id' => $supplier->id,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        ServicePart::query()->create([
            'sku' => 'M3-LOW-1', 'name' => 'Deo sa niskim stanjem', 'unit' => 'kom', 'stock_quantity' => 1, 'reserved_quantity' => 0,
            'minimum_quantity' => 2, 'average_cost_rsd' => 500, 'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);

        foreach ([$admin, $this->person('superadmin', 'parts')] as $viewer) {
            $html = $this->root((string) $this->actingAs($viewer)->get('/admin/service-parts')->assertOk()->getContent());

            self::assertSame(1, substr_count($html, '<h1 '));
            self::assertSame(1, substr_count($html, 'a1-btn a1-btn--primary'));
            self::assertSame(0, preg_match('/class="(?:[^"]*\s)?(?:'.self::OLD_CLASSES.')(?:\s[^"]*)?"/', $html), 'Old classes must be gone.');
            // Stock value 5 x 1.000 + 1 x 500, with the Serbian number format.
            self::assertStringContainsString('5.500,00 RSD', $html);
            self::assertStringContainsString('data-label="Raspoloživo"><strong>4,00</strong>', $html);
            self::assertStringContainsString('aria-label="Uredi deo M3-OK-1"', $html);
            self::assertStringContainsString('class="a1-pill a1-pill--warning">Nizak lager</span>', $html);
            self::assertStringContainsString('Dobavljač M3', $html);
            self::assertStringContainsString('Još nema kretanja.', $html);
            self::assertStringContainsString('href="'.route('admin.service-part-purchases.index').'"', $html);
            self::assertStringContainsString('href="'.route('admin.service-part-suppliers.index').'"', $html);

            $forms = $this->forms($html);
            foreach ($forms as $form) {
                if (!str_contains($form, 'method="get"')) {
                    self::assertStringContainsString('name="_token"', $form);
                }
            }
            self::assertSame(1, $this->countForms($forms, '/admin/service-parts', 'a1-form" method="post" action="'.route('admin.service-parts.store').'"'));
            self::assertSame(2, substr_count($html, 'name="_method" value="PUT"'));
            self::assertSame(2, substr_count($html, 'name="idempotency_key"'));
            self::assertSame(2, substr_count($html, '/adjust"'));
        }

        // The filters keep their query parameters.
        $low = $this->root((string) $this->actingAs($admin)->get('/admin/service-parts?filter=low&q=niskim')->assertOk()->getContent());
        self::assertStringContainsString('M3-LOW-1', $low);
        self::assertStringNotContainsString('M3-OK-1', $low);
        self::assertStringContainsString('<option value="low" selected>', $low);
        $none = $this->root((string) $this->actingAs($admin)->get('/admin/service-parts?q=nema-takvog')->assertOk()->getContent());
        self::assertStringContainsString('Nema rezervnih delova za izabrani filter.', $none);
        self::assertStringContainsString('class="a1-empty"', $none);
    }

    public function test_purchase_requests_list_and_detail_follow_the_workflow_with_confirmations(): void
    {
        $admin = $this->person('admin', 'purchase');
        $supplier = ServicePartSupplier::query()->create([
            'code' => 'DOB-M3B', 'name' => 'Dobavljač nabavke', 'lead_time_days' => 3, 'is_active' => true,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $part = ServicePart::query()->create([
            'sku' => 'M3-BUY-1', 'name' => 'Deo za nabavku', 'unit' => 'kom', 'stock_quantity' => 2, 'reserved_quantity' => 0,
            'minimum_quantity' => 2, 'average_cost_rsd' => 500, 'preferred_supplier_id' => $supplier->id,
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        $this->actingAs($admin)->post('/admin/service-part-purchases', [
            'supplier_id' => $supplier->id,
            'items' => [['service_part_id' => $part->id, 'ordered_quantity' => 3, 'unit_cost_rsd' => 700]],
        ])->assertRedirect()->assertSessionHas('status');
        $purchase = ServicePartPurchaseRequest::query()->sole();
        $number = $purchase->request_number;

        foreach ([$admin, $this->person('superadmin', 'purchase')] as $viewer) {
            $html = $this->root((string) $this->actingAs($viewer)->get('/admin/service-part-purchases')->assertOk()->getContent());

            self::assertSame(1, substr_count($html, '<h1 '));
            self::assertSame(1, substr_count($html, 'a1-btn a1-btn--primary'));
            self::assertSame(0, preg_match('/class="(?:[^"]*\s)?(?:'.self::OLD_CLASSES.')(?:\s[^"]*)?"/', $html), 'Old classes must be gone.');
            self::assertStringContainsString($number, $html);
            self::assertStringContainsString('class="a1-pill">Nacrt</span>', $html);
            self::assertStringContainsString('data-label="Vrednost">2.100,00 RSD', $html);
            self::assertStringContainsString('aria-label="Otvori zahtev '.$number.'"', $html);
            // The add-line script hooks and the line template keep working.
            self::assertStringContainsString('data-purchase-form', $html);
            self::assertStringContainsString('data-add-purchase-item', $html);
            self::assertStringContainsString('data-purchase-items', $html);
            self::assertStringContainsString('<template data-purchase-item-template>', $html);
            self::assertStringContainsString('name="items[__INDEX__][service_part_id]"', $html);
            self::assertStringContainsString('data-remove-purchase-item', $html);
            self::assertStringContainsString('M3-BUY-1 · Deo za nabavku', $html);
        }
        $filtered = $this->root((string) $this->actingAs($admin)->get('/admin/service-part-purchases?status=received')->assertOk()->getContent());
        self::assertStringContainsString('Nema zahteva za nabavku.', $filtered);

        // Draft: one primary action (submit), cancel lives in the danger zone with a confirmation.
        $draft = $this->root((string) $this->actingAs($admin)->get('/admin/service-part-purchases/'.$purchase->id)->assertOk()->getContent());
        self::assertSame(1, substr_count($draft, '<h1 '));
        self::assertSame(1, substr_count($draft, 'a1-btn a1-btn--primary'));
        self::assertStringContainsString('action="'.route('admin.service-part-purchases.submit', $purchase).'"', $draft);
        self::assertStringNotContainsString('/order"', $draft);
        self::assertStringNotContainsString('/receive"', $draft);
        self::assertStringContainsString('2.100,00 RSD', $draft);
        $forms = $this->forms($draft);
        foreach ($forms as $form) {
            self::assertStringContainsString('name="_token"', $form);
        }
        self::assertSame(1, $this->countForms($forms, '/service-part-purchases/'.$purchase->id.'/cancel', 'data-confirm="Otkazati zahtev za nabavku '.$number.'?"'));

        // Ordered: the receive form keeps its confirmation.
        $this->actingAs($admin)->post('/admin/service-part-purchases/'.$purchase->id.'/submit')->assertRedirect();
        $this->actingAs($admin)->post('/admin/service-part-purchases/'.$purchase->id.'/order')->assertRedirect();
        $ordered = $this->root((string) $this->actingAs($admin)->get('/admin/service-part-purchases/'.$purchase->id)->assertOk()->getContent());
        self::assertMatchesRegularExpression('/a1-pill a1-pill--info">Poručeno</', $ordered);
        self::assertStringContainsString('data-confirm="Potvrditi prijem svih stavki na servisni lager?"', $ordered);
        self::assertSame(1, substr_count($ordered, 'a1-btn a1-btn--primary'));

        // Received: terminal, no transition forms and no danger zone, the pill says so.
        $this->actingAs($admin)->post('/admin/service-part-purchases/'.$purchase->id.'/receive')->assertRedirect();
        $received = $this->root((string) $this->actingAs($admin)->get('/admin/service-part-purchases/'.$purchase->id)->assertOk()->getContent());
        self::assertMatchesRegularExpression('/a1-pill a1-pill--ok">Primljeno</', $received);
        self::assertStringNotContainsString('/cancel"', $received);
        self::assertStringNotContainsString('a1-btn--primary', $received);
        self::assertStringContainsString('M3-BUY-1', $received);
    }

    public function test_teams_page_keeps_forms_checkboxes_and_the_deactivation_confirmation(): void
    {
        $admin = $this->person('admin', 'teams');
        $team = FieldServiceTeam::query()->create([
            'code' => 'M3-TIM', 'name' => 'Ekipa Tri', 'team_type' => 'internal', 'phone' => '060111222',
            'is_active' => true, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
        FieldServiceTeam::query()->create([
            'code' => 'M3-OFF', 'name' => 'Ugašena ekipa', 'team_type' => 'external',
            'is_active' => false, 'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);

        foreach ([$admin, $this->person('superadmin', 'teams')] as $viewer) {
            $html = $this->root((string) $this->actingAs($viewer)->get('/admin/field-service-teams')->assertOk()->getContent());

            self::assertSame(1, substr_count($html, '<h1 '));
            self::assertSame(1, substr_count($html, 'a1-btn a1-btn--primary'));
            self::assertStringContainsString('aria-label="Nazad na vrh"', $html);
            self::assertSame(0, preg_match('/class="(?:[^"]*\s)?(?:'.self::OLD_CLASSES.')(?:\s[^"]*)?"/', $html), 'Old classes must be gone.');
            self::assertStringContainsString('class="a1-pill a1-pill--ok">Aktivna</span>', $html);
            self::assertStringContainsString('class="a1-pill a1-pill--off">Neaktivna</span>', $html);
            self::assertStringContainsString('M3-TIM · Interna ekipa · 0 aktivnih naloga', $html);
            self::assertStringContainsString('aria-label="Deaktiviraj ekipu Ekipa Tri"', $html);
            self::assertStringNotContainsString('aria-label="Deaktiviraj ekipu Ugašena ekipa"', $html);
            self::assertStringContainsString('data-confirm="Deaktivirati ekipu Ekipa Tri?"', $html);

            $forms = $this->forms($html);
            foreach ($forms as $form) {
                if (!str_contains($form, 'method="get"')) {
                    self::assertStringContainsString('name="_token"', $form);
                }
            }
            self::assertSame(1, $this->countForms($forms, '/field-service-teams"', 'a1-form" method="post"'));
            self::assertSame(2, substr_count($html, 'name="_method" value="PUT"'));
            self::assertSame(1, substr_count($html, 'name="_method" value="DELETE"'));
            self::assertSame(3, substr_count($html, 'name="is_active" value="1"'));
            self::assertSame(1, preg_match('/name="team_id" value="'.$team->id.'"/', $html));
        }

        $none = $this->root((string) $this->actingAs($admin)->get('/admin/field-service-teams?q=nepostojeca')->assertOk()->getContent());
        self::assertStringContainsString('Još nema definisanih terenskih ekipa.', $none);
        self::assertStringContainsString('class="a1-empty"', $none);
    }

    /** The page root only, without the application shell. */
    private function root(string $html): string
    {
        $start = strpos($html, 'class="a1-ops"');
        self::assertNotFalse($start, 'page root .a1-ops missing');
        $end = strpos($html, '</main>', $start);

        return substr($html, $start, ($end === false ? strlen($html) : $end) - $start);
    }

    /** @return list<string> */
    private function forms(string $html): array
    {
        preg_match_all('/<form\b[^>]*>.*?<\/form>/s', $html, $matches);
        self::assertNotEmpty($matches[0], 'The page has forms.');

        return $matches[0];
    }

    /** @param list<string> $forms */
    private function countForms(array $forms, string $actionNeedle, string $contains = ''): int
    {
        return count(array_filter($forms, static fn (string $form): bool => str_contains($form, $actionNeedle) && ($contains === '' || str_contains($form, $contains))));
    }

    private function person(string $role, string $tag): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => 'm3fs-'.$role.'-'.$tag, 'email' => 'm3fs-'.$role.'-'.$tag.'@example.test', 'first_name' => 'M3',
            'password_hash' => Hash::make('TestOnlyM3!'), 'status' => 'active',
        ]);
    }

    /** @param list<string> $permissions */
    private function groupPerson(string $username, array $permissions): User
    {
        $group = UserGroup::query()->create([
            'name' => 'M3 '.$username, 'slug' => $username, 'status' => 'active',
            'category_access_mode' => 'all', 'include_uncategorized' => true,
        ]);
        foreach ($permissions as $slug) {
            DB::table('user_group_permissions')->insert([
                'group_id' => $group->id, 'permission_id' => DB::table('permissions')->where('slug', $slug)->value('id'), 'created_at' => now(),
            ]);
        }

        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'), 'user_group_id' => $group->id,
            'username' => $username, 'email' => $username.'@example.test', 'first_name' => 'M3',
            'password_hash' => Hash::make('TestOnlyM3!'), 'status' => 'active',
        ]);
    }

    /** @return array{User,FieldWorkOrder} */
    private function workOrderScenario(): array
    {
        $customer = $this->person('user', 'customer');
        $admin = $this->person('admin', 'field');
        $product = Product::query()->create([
            'sku' => 'M3-FIELD-PRODUCT', 'name' => 'Servisni proizvod', 'slug' => 'm3-servisni-proizvod',
            'price_amount' => 30000, 'price_currency' => 'RSD', 'description' => 'Test.',
            'stock_quantity' => 5, 'low_stock_threshold' => 1, 'status' => 'active', 'created_by' => $admin->id,
        ]);
        $this->actingAs($customer)->post('/orders', [
            'idempotency_key' => 'm3-field-order-001', 'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Kupac', 'shipping_address' => 'Adresa 1', 'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000', 'shipping_phone' => '060111222', 'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();
        $order = Order::query()->with('items')->sole();
        $order->forceFill(['status' => 'shipped', 'completed_at' => now(), 'completed_by' => $admin->id])->save();
        $item = $order->items->firstOrFail();
        $case = AfterSalesCase::query()->create([
            'case_number' => 'PS-M3-001', 'order_id' => $order->id, 'opened_by' => $customer->id,
            'assigned_to' => $admin->id, 'case_type' => 'service', 'priority' => 'normal', 'status' => 'approved',
            'subject' => 'Servis', 'description' => 'Potreban servis.', 'resolution_type' => 'repair', 'resolution_summary' => 'Odobreno.',
        ]);
        $caseItem = AfterSalesCaseItem::query()->create([
            'after_sales_case_id' => $case->id, 'order_item_id' => $item->id, 'product_id' => $product->id,
            'sku_snapshot' => $item->product_sku, 'product_name_snapshot' => $item->product_name, 'quantity' => 1,
        ]);
        $this->actingAs($admin)->post('/admin/after-sales/'.$case->id.'/actions', [
            'action_type' => 'service_visit', 'assigned_to' => $admin->id,
            'scheduled_at' => now()->addHours(3)->format('Y-m-d\\TH:i'),
            'items' => [$caseItem->id => ['selected' => '1', 'quantity' => 1, 'disposition' => 'repair']],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $workOrder = AfterSalesAction::query()->with('workOrder')->sole()->workOrder;
        self::assertInstanceOf(FieldWorkOrder::class, $workOrder);

        return [$admin, $workOrder];
    }
}
