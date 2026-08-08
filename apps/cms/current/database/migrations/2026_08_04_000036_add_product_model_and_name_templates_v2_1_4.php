<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->addProductModelColumn();
        $this->backfillProductModels();
        $this->ensureModelPlaceholderInNameTemplates();
    }

    public function down(): void
    {
        // Namerno nedestruktivno: model proizvoda i izmenjeni šabloni naziva
        // moraju ostati sačuvani i nakon kontrolisanog rollback-a aplikacije.
    }

    private function addProductModelColumn(): void
    {
        if (!Schema::hasTable('products') || Schema::hasColumn('products', 'model_name')) {
            return;
        }

        Schema::table('products', static function (Blueprint $table): void {
            $table->string('model_name', 190)->nullable()->after('product_line_id');
            $table->index('model_name', 'products_model_name_index');
        });
    }

    private function backfillProductModels(): void
    {
        if (!Schema::hasTable('products')
            || !Schema::hasColumn('products', 'model_name')
            || !Schema::hasTable('specification_fields')
            || !Schema::hasTable('product_spec_values')) {
            return;
        }

        $fieldIds = DB::table('specification_fields')
            ->where(function ($query): void {
                $query->whereIn(DB::raw('LOWER(slug)'), [
                    'model',
                    'model-proizvoda',
                    'product-model',
                    'device-model',
                ])->orWhereIn(DB::raw('LOWER(name)'), [
                    'model',
                    'model proizvoda',
                    'tačan model proizvoda',
                    'tacan model proizvoda',
                ]);
            })
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($fieldIds === []) {
            return;
        }

        DB::table('product_spec_values')
            ->whereIn('field_id', $fieldIds)
            ->whereNotNull('value_text')
            ->where('value_text', '!=', '')
            ->orderBy('product_id')
            ->chunk(250, static function ($rows): void {
                foreach ($rows as $row) {
                    $value = preg_replace('/\s+/u', ' ', trim((string) $row->value_text)) ?: trim((string) $row->value_text);
                    if ($value === '') {
                        continue;
                    }

                    DB::table('products')
                        ->where('id', (int) $row->product_id)
                        ->where(function ($query): void {
                            $query->whereNull('model_name')->orWhere('model_name', '');
                        })
                        ->update(['model_name' => mb_substr($value, 0, 190)]);
                }
            });
    }

    private function ensureModelPlaceholderInNameTemplates(): void
    {
        if (!Schema::hasTable('product_types') || !Schema::hasColumn('product_types', 'name_template')) {
            return;
        }

        DB::table('product_types')
            ->whereNotNull('name_template')
            ->where('name_template', '!=', '')
            ->orderBy('id')
            ->chunkById(100, static function ($types): void {
                foreach ($types as $type) {
                    $template = trim((string) $type->name_template);
                    if ($template === '' || preg_match('/\{model\}/i', $template) === 1) {
                        continue;
                    }

                    $updated = preg_replace('/\{line\}/i', '{line} {model}', $template, 1, $lineCount);
                    if ($lineCount === 0) {
                        $updated = preg_replace('/\{brand\}/i', '{brand} {model}', $template, 1, $brandCount);
                        if ($brandCount === 0) {
                            $updated = '{model} '.$template;
                        }
                    }

                    $updated = preg_replace('/\s+/', ' ', trim((string) $updated)) ?: trim((string) $updated);
                    DB::table('product_types')->where('id', (int) $type->id)->update([
                        'name_template' => mb_substr($updated, 0, 500),
                    ]);
                }
            });
    }
};
