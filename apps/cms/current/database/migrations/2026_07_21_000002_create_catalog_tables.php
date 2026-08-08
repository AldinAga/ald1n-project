<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index('parent_id');
            $table->index(['status', 'sort_order', 'name']);
        });

        Schema::create('user_group_categories', function (Blueprint $table): void {
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['group_id', 'category_id']);
            $table->foreign('group_id')->references('id')->on('user_groups')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->index('category_id');
        });

        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->string('website_url')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'sort_order', 'name']);
        });

        Schema::create('product_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('brand_id');
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('brand_id')->references('id')->on('brands')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['brand_id', 'slug']);
            $table->index(['brand_id', 'status', 'sort_order', 'name']);
        });

        Schema::create('product_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'sort_order', 'name']);
        });

        Schema::create('specification_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->enum('data_type', ['text', 'integer', 'decimal', 'select', 'boolean'])->default('text');
            $table->enum('filter_type', ['none', 'select', 'range', 'boolean', 'text'])->default('none');
            $table->string('unit', 30)->nullable();
            $table->string('placeholder', 160)->nullable();
            $table->string('help_text', 500)->nullable();
            $table->longText('options_text')->nullable();
            $table->decimal('min_value', 18, 4)->nullable();
            $table->decimal('max_value', 18, 4)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'sort_order', 'name']);
            $table->index('filter_type');
        });

        Schema::create('product_type_fields', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_type_id');
            $table->unsignedBigInteger('field_id');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('show_in_summary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['product_type_id', 'field_id']);
            $table->foreign('product_type_id')->references('id')->on('product_types')->cascadeOnDelete();
            $table->foreign('field_id')->references('id')->on('specification_fields')->cascadeOnDelete();
            $table->index('field_id');
            $table->index(['product_type_id', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_type_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('product_line_id')->nullable();
            $table->string('sku', 100)->unique();
            $table->string('name', 190);
            $table->string('slug', 210)->unique();
            $table->decimal('price_amount', 12, 2);
            $table->enum('price_currency', ['RSD', 'EUR'])->default('RSD');
            $table->decimal('manual_commission_eur', 12, 2)->nullable();
            $table->mediumText('description');
            $table->text('notes')->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(1);
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->dateTime('deleted_at')->nullable();
            $table->foreign('product_type_id')->references('id')->on('product_types')->nullOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->foreign('product_line_id')->references('id')->on('product_lines')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->index('product_type_id');
            $table->index('brand_id');
            $table->index('product_line_id');
            $table->index('name');
            $table->index('price_amount');
            $table->index('stock_quantity');
            $table->index('status');
        });

        Schema::create('product_categories', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamp('created_at')->useCurrent();
            $table->primary(['product_id', 'category_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->index(['category_id', 'product_id']);
        });

        Schema::create('product_spec_values', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('field_id');
            $table->string('value_text', 1000)->nullable();
            $table->decimal('value_number', 18, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->timestamps();
            $table->primary(['product_id', 'field_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('field_id')->references('id')->on('specification_fields')->cascadeOnDelete();
            $table->index(['field_id', 'value_number']);
            $table->index(['field_id', 'value_boolean']);
        });

        Schema::create('product_images', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size');
            $table->unsignedSmallInteger('rotation_degrees')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        foreach (['product_images', 'product_spec_values', 'product_categories', 'products', 'product_type_fields', 'specification_fields', 'product_types', 'product_lines', 'brands', 'user_group_categories', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
