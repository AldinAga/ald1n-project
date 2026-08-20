<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProductType;
use App\Services\ProductTypeCategoryService;
use App\Services\SpecificationFieldLifecycleService;
use App\Services\StorageSpecificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class CatalogSettingsDoctorCommand extends Command
{
    protected $signature = 'app:catalog-settings-doctor {--repair : Automatski poveži kategorije i disk polja, preračunaj kapacitete i ukloni zastarele reference}';

    protected $description = 'Proveri stranice tipova proizvoda, automatske kategorije, diskove i integritet specifikacija.';

    public function handle(ProductTypeCategoryService $typeCategories, SpecificationFieldLifecycleService $fieldLifecycle, StorageSpecificationService $storage): int
    {
        $failed = false;
        $warned = false;

        foreach ([
            'product_types' => ['id', 'slug', 'category_id', 'sort_order'],
            'product_type_fields' => ['product_type_id', 'field_id', 'sort_order'],
            'specification_fields' => ['id', 'slug', 'status', 'sort_order', 'storage_role', 'storage_source_field_id'],
            'product_spec_values' => ['product_id', 'field_id', 'value_json'],
        ] as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->error('FAIL Nedostaje tabela '.$table.'.');
                $failed = true;
                continue;
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $this->error('FAIL '.$table.' nema kolonu '.$column.'.');
                    $failed = true;
                }
            }
        }
        if (!$failed) {
            $this->info('PASS Šema tipova proizvoda i strukturisanih specifikacija je spremna.');
        }

        foreach ([
            'admin.dictionary.index',
            'admin.dictionary.product-type',
            'admin.dictionary.reorder',
            'admin.dictionary.product-type.fields.reorder',
            'admin.dictionary.purge',
        ] as $routeName) {
            if (!Route::has($routeName)) {
                $this->error('FAIL Nedostaje ruta '.$routeName.'.');
                $failed = true;
            }
        }
        if (!$failed) {
            $this->info('PASS Rute posebnih stranica, Drag & Drop rasporeda i bezbednog trajnog brisanja postoje.');
        }

        foreach ([
            resource_path('views/admin/dictionary/product-type.blade.php'),
            public_path('assets/js/dictionary-sort-manager.js'),
            app_path('Services/ProductTypeCategoryService.php'),
            app_path('Services/SpecificationFieldLifecycleService.php'),
            app_path('Services/StorageSpecificationService.php'),
            database_path('migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php'),
        ] as $path) {
            if (!is_file($path) || filesize($path) === 0) {
                $this->error('FAIL Nedostaje release fajl '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).'.');
                $failed = true;
            }
        }
        if (!$failed) {
            $this->info('PASS UI i servisni fajlovi za automatske kategorije i bezbedne specifikacije postoje.');
        }

        if ($failed) {
            $this->error('Podešavanja kataloga nisu spremna.');
            return self::FAILURE;
        }

        try {
            if ($this->option('repair')) {
                $categorySummary = $typeCategories->ensureAll();
                $this->line(sprintf(
                    'Repair kategorija: pregledano %d, postojeće %d, reaktivirano %d, kreirano %d, artikli usklađeni %d.',
                    $categorySummary['examined'],
                    $categorySummary['matched'],
                    $categorySummary['reactivated'],
                    $categorySummary['created'],
                    $categorySummary['products_synced'],
                ));
                $fieldLifecycle->repairIntegrity();
                $storageSummary = $storage->repair();
                $this->line(sprintf(
                    'Repair diskova: komponente %d, ukupna polja %d, parovi %d, proizvodi %d.',
                    $storageSummary['components'],
                    $storageSummary['totals'],
                    $storageSummary['pairs'],
                    $storageSummary['products'],
                ));
            }

            $unmapped = ProductType::query()
                ->where('status', 'active')
                ->whereNull('category_id')
                ->orderBy('id')
                ->get(['id', 'name', 'slug']);
            if ($unmapped->isNotEmpty()) {
                $this->line('<fg=yellow>WARN</> '.$unmapped->count().' aktivnih tipova nema automatsku sistemsku kategoriju: '.$unmapped->pluck('name')->implode(', ').'. Pokreni komandu sa --repair.');
                $warned = true;
            } else {
                $this->info('PASS Svi aktivni tipovi imaju automatsku sistemsku kategoriju.');
            }

            [$missing, $extra] = $this->categoryMismatchCounts();
            if ($missing > 0 || $extra > 0) {
                $this->line('<fg=yellow>WARN</> Kategorije artikala nisu potpuno usklađene sa tipovima: nedostaje '.$missing.', višak '.$extra.'. Pokreni komandu sa --repair.');
                $warned = true;
            } else {
                $this->info('PASS Artikli koriste isključivo kategoriju povezanu sa svojim tipom.');
            }

            $integrity = $fieldLifecycle->integrityCounts();
            $integrityProblems = array_sum($integrity);
            if ($integrityProblems > 0) {
                $details = collect($integrity)
                    ->filter(static fn (int $count): bool => $count > 0)
                    ->map(static fn (int $count, string $key): string => $key.'='.$count)
                    ->implode(', ');
                $this->line('<fg=yellow>WARN</> Pronađene su zastarele ili nepovezane specifikacione reference: '.$details.'. Pokreni komandu sa --repair.');
                $warned = true;
            } else {
                $this->info('PASS Nema zastarelih vrednosti, pivot veza, opcija ni korelacija obrisanih specifikacionih polja.');
            }


            $storageIntegrity = $storage->integrityCounts();
            $storageProblems = array_sum($storageIntegrity);
            if ($storageProblems > 0) {
                $details = collect($storageIntegrity)
                    ->filter(static fn (int $count): bool => $count > 0)
                    ->map(static fn (int $count, string $key): string => $key.'='.$count)
                    ->implode(', ');
                $this->line('<fg=yellow>WARN</> Diskovi i ukupan kapacitet nisu potpuno usklađeni: '.$details.'. Pokreni komandu sa --repair.');
                $warned = true;
            } else {
                $this->info('PASS Pojedinačni diskovi, stari kapaciteti i automatski ukupni zbir su usklađeni.');
            }
        } catch (Throwable $exception) {
            $this->error('FAIL Runtime provera kataloga nije uspela: '.$exception::class.': '.$exception->getMessage());
            return self::FAILURE;
        }

        if ($warned) {
            $this->line('<fg=yellow>Podešavanja kataloga rade, ali zahtevaju navedeno usklađivanje.</>');
            return self::SUCCESS;
        }

        $this->info('Podešavanja kataloga su spremna.');
        return self::SUCCESS;
    }

    /** @return array{0:int,1:int} */
    private function categoryMismatchCounts(): array
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_categories')) {
            return [0, 0];
        }

        $base = DB::table('products as products')
            ->join('product_types as types', 'types.id', '=', 'products.product_type_id')
            ->whereNotNull('types.category_id')
            ->whereNull('products.deleted_at');

        $missing = (clone $base)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('product_categories as mapped')
                    ->whereColumn('mapped.product_id', 'products.id')
                    ->whereColumn('mapped.category_id', 'types.category_id');
            })
            ->count();

        $extra = (clone $base)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('product_categories as extra')
                    ->whereColumn('extra.product_id', 'products.id')
                    ->whereColumn('extra.category_id', '!=', 'types.category_id');
            })
            ->count();

        return [(int) $missing, (int) $extra];
    }
}
