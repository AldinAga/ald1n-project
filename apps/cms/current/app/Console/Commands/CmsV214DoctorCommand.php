<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProductType;
use App\Models\SpecificationField;
use App\Services\CatalogDictionary;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use RuntimeException;
use Throwable;

final class CmsV214DoctorCommand extends Command
{
    protected $signature = 'app:cms-v2-1-4-doctor {--repair : Dopuni prilagođene šablone naziva tokenom {model}}';

    protected $description = 'Proveri v2.1.4 sistem dugmića, model proizvoda, automatski naziv i bezbedno trajno brisanje artikala.';

    public function handle(CatalogDictionary $dictionary): int
    {
        $failed = false;
        $warned = false;

        foreach ([
            'products' => ['id', 'sku', 'model_name', 'product_type_id', 'brand_id', 'product_line_id'],
            'product_types' => ['id', 'name', 'name_template'],
            'product_images' => ['id', 'product_id', 'storage_disk', 'file_path'],
        ] as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->error('FAIL Nedostaje tabela '.$table.'.');
                $failed = true;
                continue;
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $this->error('FAIL '.$table.' nema kolonu '.$column.'. Pokreni php artisan migrate --force.');
                    $failed = true;
                }
            }
        }
        if (!$failed) {
            $this->info('PASS Šema modela proizvoda i galerije je spremna.');
        }

        foreach ([
            'catalog.index',
            'admin.products.store',
            'admin.products.update',
            'admin.products.name-preview',
            'admin.products.purge',
            'admin.dictionary.product-type',
            'admin.dictionary.product-type.fields.reorder',
        ] as $routeName) {
            if (!Route::has($routeName)) {
                $this->error('FAIL Nedostaje ruta '.$routeName.'.');
                $failed = true;
            }
        }
        if (!$failed) {
            $this->info('PASS Rute čuvanja, pregleda imena i trajnog brisanja su registrovane.');
        }

        $files = [
            app_path('Services/ProductDeletionService.php'),
            app_path('Services/ProductTemplateService.php'),
            app_path('Http/Requests/ProductRequest.php'),
            resource_path('views/admin/products/form.blade.php'),
            resource_path('views/components/icon.blade.php'),
            public_path('assets/css/app.css'),
            database_path('migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php'),
        ];
        foreach ($files as $path) {
            if (!is_file($path) || filesize($path) === 0) {
                $this->error('FAIL Nedostaje release fajl '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).'.');
                $failed = true;
            }
        }

        if (!$failed) {
            $css = (string) file_get_contents(public_path('assets/css/app.css'));
            $form = (string) file_get_contents(resource_path('views/admin/products/form.blade.php'));
            $deletion = (string) file_get_contents(app_path('Services/ProductDeletionService.php'));
            $template = (string) file_get_contents(app_path('Services/ProductTemplateService.php'));

            foreach (['.button-primary', '.button-secondary', '.button-success', '.button-warning', '.button-danger', '--button-height'] as $needle) {
                if (!str_contains($css, $needle)) {
                    $this->error('FAIL CSS sistem nema '.$needle.'.');
                    $failed = true;
                }
            }
            if (!str_contains($form, 'name="model_name"') || !str_contains($form, 'purge-product') || !str_contains($form, 'delete_images')) {
                $this->error('FAIL Forma artikla nema model proizvoda ili kontrolu trajnog brisanja slika.');
                $failed = true;
            }
            if (!str_contains($template, "'model' => trim((string) (\$data['model_name'] ?? ''))") || !str_contains($deletion, 'deleteDirectory')) {
                $this->error('FAIL Backend ne povezuje model sa nazivom ili nema kontrolisano brisanje direktorijuma slika.');
                $failed = true;
            }
        }
        if (!$failed) {
            $this->info('PASS UI, semantičke boje, model i servis trajnog brisanja postoje.');
        }

        if ($failed) {
            $this->error('v2.1.4.1 nije spreman.');
            return self::FAILURE;
        }

        try {
            if ($this->option('repair')) {
                $updated = $this->repairTemplates();
                $normalized = $this->normalizeProductModels();
                $this->line('Repair šablona naziva: izmenjeno '.$updated.'.');
                $this->line('Normalizacija modela proizvoda: izmenjeno '.$normalized.'.');
            }

            $missingModelToken = DB::table('product_types')
                ->whereNotNull('name_template')
                ->where('name_template', '!=', '')
                ->whereRaw('LOWER(name_template) NOT LIKE ?', ['%{model}%'])
                ->count();

            if ($missingModelToken > 0) {
                $this->line('<fg=yellow>WARN</> '.$missingModelToken.' prilagođenih šablona naziva nema {model}. Pokreni komandu sa --repair.');
                $warned = true;
            } else {
                $this->info('PASS Svi prilagođeni šabloni naziva podržavaju Model proizvoda.');
            }

            $invalidModels = DB::table('products')
                ->whereNotNull('model_name')
                ->where(function ($query): void {
                    $query->whereRaw('model_name <> TRIM(model_name)')
                        ->orWhere('model_name', 'like', '%  %');
                })
                ->count();
            if ($invalidModels > 0) {
                $this->line('<fg=yellow>WARN</> '.$invalidModels.' modela proizvoda ima višak razmaka; ponovnim čuvanjem biće normalizovani.');
                $warned = true;
            } else {
                $this->info('PASS Sačuvani modeli proizvoda su normalizovani.');
            }


            $renderedTypes = $this->renderProductTypeFieldForms($dictionary);
            $this->info('PASS Zajednički formular specifikacija je renderovan za '.$renderedTypes.' tipova proizvoda.');
        } catch (Throwable $exception) {
            $this->error('FAIL Runtime provera v2.1.4.1 nije uspela: '.$exception::class.': '.$exception->getMessage());
            return self::FAILURE;
        }

        if ($warned) {
            $this->line('<fg=yellow>v2.1.4.1 radi, ali zahteva navedeno usklađivanje.</>');
            return self::SUCCESS;
        }

        $this->info('v2.1.4.1 stranice tipova proizvoda, dugmad, model i životni ciklus artikla su spremni.');
        return self::SUCCESS;
    }

    private function renderProductTypeFieldForms(CatalogDictionary $dictionary): int
    {
        if (!Schema::hasTable('product_types') || !Schema::hasTable('specification_fields')) {
            throw new RuntimeException('Nedostaju tabele potrebne za render stranica tipova proizvoda.');
        }

        $previousRequest = app()->bound('request') ? app('request') : null;
        $session = app('session')->driver();
        try {
            $session->start();
            if (!is_string($session->token()) || $session->token() === '') {
                $session->regenerateToken();
            }
        } catch (Throwable) {
            $session->put('_token', Str::random(40));
        }

        $request = Request::create('/admin/catalog-settings/product-types', 'GET');
        $request->setLaravelSession($session);
        app()->instance('request', $request);
        View::share('errors', new ViewErrorBag());

        try {
            $fields = SpecificationField::query()
                ->with(['parentField', 'options'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
            $types = ProductType::query()
                ->with([
                    'category',
                    'fields.options' => fn ($query) => $query->orderBy('sort_order')->orderBy('label'),
                ])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            foreach ($types as $type) {
                $assignedFields = $type->fields->keyBy('id');
                $orderedFields = $fields->sortBy(static function (SpecificationField $field) use ($assignedFields): array {
                    $assigned = $assignedFields->get($field->id);

                    return [
                        $assigned === null ? 1 : 0,
                        (int) ($assigned?->pivot?->sort_order ?? $field->sort_order ?? 100000),
                        mb_strtolower((string) $field->name),
                    ];
                })->values();

                $html = view('admin.dictionary.fields', [
                    'resource' => 'product-types',
                    'definition' => $dictionary->definition('product-types'),
                    'definitions' => $dictionary->definitions(),
                    'item' => $type,
                    'categories' => collect(),
                    'brands' => collect(),
                    'fields' => $fields,
                    'orderedFields' => $orderedFields,
                    'selectableFields' => collect(),
                    'dependencyMaps' => [],
                    'productTypeCompact' => false,
                ])->with('errors', new ViewErrorBag())->render();

                if (!str_contains($html, 'template-field-table')) {
                    throw new RuntimeException('Render tipa '.$type->slug.' nema očekivanu tabelu specifikacija.');
                }
            }

            return $types->count();
        } finally {
            if ($previousRequest instanceof Request) {
                app()->instance('request', $previousRequest);
            }
        }
    }

    private function repairTemplates(): int
    {
        $updated = 0;
        DB::table('product_types')
            ->whereNotNull('name_template')
            ->where('name_template', '!=', '')
            ->whereRaw('LOWER(name_template) NOT LIKE ?', ['%{model}%'])
            ->orderBy('id')
            ->chunkById(100, static function ($types) use (&$updated): void {
                foreach ($types as $type) {
                    $template = trim((string) $type->name_template);
                    $candidate = preg_replace('/\{line\}/i', '{line} {model}', $template, 1, $lineCount);
                    if ($lineCount === 0) {
                        $candidate = preg_replace('/\{brand\}/i', '{brand} {model}', $template, 1, $brandCount);
                        if ($brandCount === 0) {
                            $candidate = '{model} '.$template;
                        }
                    }
                    $candidate = preg_replace('/\s+/', ' ', trim((string) $candidate)) ?: trim((string) $candidate);
                    DB::table('product_types')->where('id', (int) $type->id)->update([
                        'name_template' => mb_substr($candidate, 0, 500),
                    ]);
                    $updated++;
                }
            });

        return $updated;
    }
    private function normalizeProductModels(): int
    {
        if (!Schema::hasTable('products') || !Schema::hasColumn('products', 'model_name')) {
            return 0;
        }

        $updated = 0;
        DB::table('products')
            ->whereNotNull('model_name')
            ->orderBy('id')
            ->chunkById(250, static function ($products) use (&$updated): void {
                foreach ($products as $product) {
                    $current = (string) $product->model_name;
                    $normalized = preg_replace('/\s+/u', ' ', trim($current)) ?: trim($current);
                    $normalized = $normalized === '' ? null : mb_substr($normalized, 0, 190);
                    if ($normalized === $current) {
                        continue;
                    }
                    DB::table('products')->where('id', (int) $product->id)->update(['model_name' => $normalized]);
                    $updated++;
                }
            });

        return $updated;
    }

}
