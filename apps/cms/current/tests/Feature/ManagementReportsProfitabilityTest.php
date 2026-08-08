<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ReportDelivery;
use App\Models\ReportSchedule;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ManagementReportsProfitabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadministrator_can_open_management_dashboard_and_exports(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get('/admin/reports')->assertOk()->assertSee('Izveštaji i profitabilnost');
        $csv = $this->actingAs($admin)->get('/admin/reports/management.csv');
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        self::assertStringContainsString('UPRAVLJAČKI IZVEŠTAJ', $csv->getContent());

        $pdf = $this->actingAs($admin)->get('/admin/reports/management.pdf');
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-1.4', $pdf->getContent());
    }

    public function test_schedule_is_created_and_manual_run_is_deduplicated_per_request(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->post('/admin/reports/schedules', [
            'name' => 'Nedeljni direktor',
            'report_type' => 'management_summary',
            'frequency' => 'weekly',
            'send_time' => '08:00',
            'weekday' => 1,
            'timezone' => 'Europe/Belgrade',
            'recipients' => "direktor@example.test\nfinansije@example.test",
            'formats' => ['pdf', 'csv'],
            'filters' => ['scope' => 'completed', 'group_by' => 'brand', 'supplier_user_id' => 0],
            'is_active' => '1',
        ])->assertRedirect();

        $schedule = ReportSchedule::query()->sole();
        self::assertTrue($schedule->is_active);
        self::assertCount(2, $schedule->recipients_json);
        self::assertNotNull($schedule->next_run_at);

        $this->actingAs($admin)->post('/admin/reports/schedules/'.$schedule->id.'/run')->assertRedirect();
        self::assertSame(2, ReportDelivery::query()->count());
        self::assertSame(2, ReportDelivery::query()->where('status', 'pending')->count());
    }

    private function superAdmin(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'reports-superadmin',
            'email' => 'reports-superadmin@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }
}
