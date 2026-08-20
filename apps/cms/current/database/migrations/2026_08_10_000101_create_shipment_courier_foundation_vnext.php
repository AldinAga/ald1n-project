<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('courier_services')) {
            Schema::create('courier_services', static function (Blueprint $table): void {
                $table->id();
                $table->string('name', 120);
                $table->string('slug', 120)->unique();
                $table->string('tracking_url', 500);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'sort_order'], 'courier_services_active_sort_index');
                $table->index(['is_default', 'is_active'], 'courier_services_default_active_index');
                $table->foreign('created_by', 'courier_services_created_by_foreign')->references('id')->on('users')->nullOnDelete();
                $table->foreign('updated_by', 'courier_services_updated_by_foreign')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('courier_services') && DB::table('courier_services')->count() === 0) {
            $now = now();
            DB::table('courier_services')->insert([
                [
                    'name' => 'Post Express',
                    'slug' => 'post-express',
                    'tracking_url' => 'https://www.posta.rs/lat/alati/pracenje-posiljke.aspx',
                    'is_active' => true,
                    'is_default' => true,
                    'sort_order' => 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'D Express',
                    'slug' => 'd-express',
                    'tracking_url' => 'https://www.dexpress.rs/rs/pracenje-posiljaka',
                    'is_active' => true,
                    'is_default' => false,
                    'sort_order' => 20,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'BEX Express',
                    'slug' => 'bex-express',
                    'tracking_url' => 'https://bexexpress.rs/pracenje-posiljke',
                    'is_active' => true,
                    'is_default' => false,
                    'sort_order' => 30,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'AKS Express',
                    'slug' => 'aks-express',
                    'tracking_url' => 'https://www.aks.rs/pracenje-posiljke/',
                    'is_active' => true,
                    'is_default' => false,
                    'sort_order' => 40,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'City Express',
                    'slug' => 'city-express',
                    'tracking_url' => 'https://www.cityexpress.rs/pracenje-posiljke',
                    'is_active' => true,
                    'is_default' => false,
                    'sort_order' => 50,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        if (!Schema::hasTable('order_shipments')) {
            Schema::create('order_shipments', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('order_id')->unique();
                $table->string('shipment_method', 40)->default('courier');
                $table->unsignedBigInteger('courier_service_id')->nullable();
                $table->string('courier_name_snapshot', 190)->nullable();
                $table->string('courier_tracking_url_snapshot', 500)->nullable();
                $table->dateTime('shipped_at');
                $table->string('recipient_name', 190);
                $table->string('recipient_phone', 60)->nullable();
                $table->string('tracking_number_snapshot', 120)->nullable();
                $table->text('note')->nullable();
                $table->string('proof_disk', 40)->nullable();
                $table->string('proof_path', 500)->nullable();
                $table->string('proof_original_name', 255)->nullable();
                $table->string('proof_mime_type', 120)->nullable();
                $table->unsignedBigInteger('proof_size')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();

                $table->index(['shipped_at', 'shipment_method'], 'order_shipments_shipped_method_index');
                $table->index('courier_service_id', 'order_shipments_courier_index');
                $table->foreign('order_id', 'order_shipments_order_id_foreign')->references('id')->on('orders')->cascadeOnDelete();
                $table->foreign('courier_service_id', 'order_shipments_courier_service_id_foreign')->references('id')->on('courier_services')->nullOnDelete();
                $table->foreign('recorded_by', 'order_shipments_recorded_by_foreign')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipments');
        Schema::dropIfExists('courier_services');
    }
};
