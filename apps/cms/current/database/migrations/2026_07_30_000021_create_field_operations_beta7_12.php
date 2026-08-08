<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTeams();
        $this->createWorkOrders();
        $this->createAttachments();
        $this->seedPermissions();
        $this->backfillPhysicalActions();
    }

    public function down(): void
    {
        Schema::dropIfExists('field_work_order_attachments');
        Schema::dropIfExists('field_work_orders');
        Schema::dropIfExists('field_service_teams');
    }

    private function createTeams(): void
    {
        if (Schema::hasTable('field_service_teams')) return;

        Schema::create('field_service_teams', static function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 190);
            $table->string('team_type', 30)->default('internal');
            $table->string('contact_person', 190)->nullable();
            $table->string('phone', 80)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('vehicle_registration', 80)->nullable();
            $table->string('service_area', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'name'], 'field_service_teams_active_name_index');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createWorkOrders(): void
    {
        if (Schema::hasTable('field_work_orders')) return;

        Schema::create('field_work_orders', static function (Blueprint $table): void {
            $table->id();
            $table->string('work_order_number', 50)->unique();
            $table->unsignedBigInteger('after_sales_action_id')->unique();
            $table->unsignedBigInteger('field_service_team_id')->nullable();
            $table->string('status', 30)->default('planned');
            $table->timestamp('planned_start_at')->nullable();
            $table->timestamp('planned_end_at')->nullable();
            $table->timestamp('en_route_at')->nullable();
            $table->timestamp('on_site_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('status_by')->nullable();
            $table->string('customer_name_snapshot', 255)->nullable();
            $table->string('customer_phone_snapshot', 100)->nullable();
            $table->text('service_address_snapshot')->nullable();
            $table->string('route_reference', 190)->nullable();
            $table->text('public_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->text('completion_result')->nullable();
            $table->decimal('travel_km', 10, 2)->nullable();
            $table->decimal('travel_cost_rsd', 15, 2)->default(0);
            $table->decimal('labor_cost_rsd', 15, 2)->default(0);
            $table->decimal('parts_cost_rsd', 15, 2)->default(0);
            $table->decimal('total_cost_rsd', 15, 2)->default(0);
            $table->text('cancellation_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['field_service_team_id', 'status', 'planned_start_at'], 'field_work_orders_team_schedule_index');
            $table->index(['status', 'planned_start_at'], 'field_work_orders_status_schedule_index');
            $table->foreign('after_sales_action_id')->references('id')->on('after_sales_actions')->cascadeOnDelete();
            $table->foreign('field_service_team_id')->references('id')->on('field_service_teams')->nullOnDelete();
            $table->foreign('status_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createAttachments(): void
    {
        if (Schema::hasTable('field_work_order_attachments')) return;

        Schema::create('field_work_order_attachments', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('field_work_order_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('visibility', 20)->default('internal');
            $table->string('file_type', 30)->default('proof');
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('path', 500);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->index(['field_work_order_id', 'visibility'], 'field_work_order_attachment_visibility_index');
            $table->foreign('field_work_order_id')->references('id')->on('field_work_orders')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function seedPermissions(): void
    {
        if (!Schema::hasTable('permissions')) return;

        foreach ([
            ['slug' => 'field_operations.view', 'name' => 'Pregled terenskih operacija', 'description' => 'Pregled kalendara, radnih naloga i rasporeda terenskih ekipa.', 'sort_order' => 111],
            ['slug' => 'field_operations.manage', 'name' => 'Upravljanje terenskim operacijama', 'description' => 'Raspoređivanje ekipa, evidencija dolaska, troškova i završetka radnog naloga.', 'sort_order' => 112],
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission + ['created_at' => now()],
            );
        }
    }

    private function backfillPhysicalActions(): void
    {
        if (!Schema::hasTable('after_sales_actions') || !Schema::hasTable('field_work_orders')) return;

        $rows = DB::table('after_sales_actions as actions')
            ->join('after_sales_cases as cases', 'cases.id', '=', 'actions.after_sales_case_id')
            ->join('orders', 'orders.id', '=', 'cases.order_id')
            ->leftJoin('field_work_orders as work_orders', 'work_orders.after_sales_action_id', '=', 'actions.id')
            ->whereNull('work_orders.id')
            ->whereIn('actions.action_type', ['service_visit', 'replacement_dispatch', 'return_receipt'])
            ->select([
                'actions.id', 'actions.status', 'actions.scheduled_at', 'actions.due_at', 'actions.created_by',
                'actions.updated_by', 'actions.reference', 'actions.public_note', 'actions.internal_note',
                'actions.created_at', 'actions.updated_at', 'orders.shipping_full_name', 'orders.shipping_phone',
                'orders.shipping_address', 'orders.shipping_city', 'orders.shipping_postal_code',
            ])->orderBy('actions.id')->get();

        foreach ($rows as $row) {
            $status = match ((string) $row->status) {
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                'in_progress' => 'on_site',
                default => 'planned',
            };
            $address = trim(implode(', ', array_filter([
                (string) $row->shipping_address,
                trim((string) $row->shipping_postal_code.' '.(string) $row->shipping_city),
            ])));
            DB::table('field_work_orders')->insert([
                'work_order_number' => 'RN-'.date('Ymd').'-'.str_pad((string) $row->id, 6, '0', STR_PAD_LEFT),
                'after_sales_action_id' => $row->id,
                'status' => $status,
                'planned_start_at' => $row->scheduled_at,
                'planned_end_at' => $row->scheduled_at !== null ? date('Y-m-d H:i:s', strtotime((string) $row->scheduled_at.' +2 hours')) : null,
                'completed_at' => $status === 'completed' ? $row->updated_at : null,
                'cancelled_at' => $status === 'cancelled' ? $row->updated_at : null,
                'customer_name_snapshot' => $row->shipping_full_name,
                'customer_phone_snapshot' => $row->shipping_phone,
                'service_address_snapshot' => $address !== '' ? $address : null,
                'route_reference' => $row->reference,
                'public_note' => $row->public_note,
                'internal_note' => $row->internal_note,
                'created_by' => $row->created_by,
                'updated_by' => $row->updated_by,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }
};
