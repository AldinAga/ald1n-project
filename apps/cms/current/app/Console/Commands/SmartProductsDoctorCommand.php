<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ProductType;
use App\Services\ProductCompletenessService;
use App\Services\ProductTemplateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class SmartProductsDoctorCommand extends Command
{
    protected $signature = 'app:smart-products-doctor
        {--repair : Pokreni migracije i ponovo obračunaj kompletnost svih artikala}';

    protected $description = 'Proverava šablone proizvoda, kompletnost, kloniranje, bulk izmene i automatske nazive.';

    public function handle(ProductCompletenessService $completeness, ProductTemplateService $templates): int
    {
        if ($this->option('repair')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                $this->output->write(Artisan::output());
                $result = $completeness->recalculateAll();
                $this->info(sprintf(
                    'Ponovo obračunata kompletnost za %d artikala; %d aktivnih artikala vraćeno je u nacrt.',
                    $result['examined'],
                    $result['downgraded'],
                ));
            } catch (Throwable $exception) {
                $this->error('Repair nije uspeo: '.$exception->getMessage());
                return self::FAILURE;
            }
        }

        $failed = false;
        $required = [
            'product_types' => ['id', 'name_template', 'auto_name_enabled', 'minimum_completeness_percent', 'default_product_status', 'required_core_fields_json'],
            'product_type_fields' => ['product_type_id', 'field_id', 'default_value', 'default_detail', 'completeness_weight', 'include_in_name'],
            'products' => ['id', 'product_type_id', 'model_name', 'completeness_percent', 'name_is_manual', 'source_product_id'],
            'product_spec_values' => ['product_id', 'field_id', 'value_text', 'value_detail', 'value_number', 'value_boolean'],
            'specification_options' => ['id', 'field_id', 'value', 'status'],
        ];

        foreach ($required as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->line('<fg=red>FAIL</> Nedostaje tabela '.$table.'.');
                $failed = true;
                continue;
            }
            $missing = array_values(array_diff($columns, Schema::getColumnListing($table)));
            if ($missing !== []) {
                $this->line('<fg=red>FAIL</> '.$table.' nema kolone: '.implode(', ', $missing));
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> '.$table.' je spremna.');
            }
        }

        if ($failed) {
            $this->warn('Pokreni: php artisan app:smart-products-doctor --repair');
            return self::FAILURE;
        }

        $requiredRoutes = [
            'admin.products.bulk',
            'admin.products.bulk.process',
            'admin.products.clone',
            'admin.products.clone.store',
            'admin.products.name-preview',
            'admin.products.regenerate-name',
        ];
        $missingRoutes = array_values(array_filter($requiredRoutes, static fn (string $name): bool => !Route::has($name)));
        if ($missingRoutes !== []) {
            $this->line('<fg=red>FAIL</> Nedostaju rute: '.implode(', ', $missingRoutes));
            $failed = true;
        } else {
            $this->line('<fg=green>PASS</> Bulk, clone i name-preview rute su registrovane.');
        }

        try {
            $invalidTypes = 0;
            $unknownPlaceholders = [];
            ProductType::query()->with('fields')->orderBy('id')->chunkById(100, function ($types) use (&$invalidTypes, &$unknownPlaceholders, $templates): void {
                foreach ($types as $type) {
                    $minimum = (int) $type->minimum_completeness_percent;
                    if ($minimum < 0 || $minimum > 100 || !in_array($type->default_product_status, ['draft', 'active', 'inactive'], true)) {
                        $invalidTypes++;
                    }
                    $requiredCore = $templates->requiredCoreFields($type);
                    $rawCore = $type->required_core_fields_json;
                    if ($rawCore !== null && $rawCore !== '' && !is_array($rawCore) && json_decode((string) $rawCore, true) === null) {
                        $invalidTypes++;
                    }

                    $template = trim((string) $type->name_template);
                    if ($template === '') continue;
                    preg_match_all('/\{([a-z0-9_\-]+)\}/i', $template, $matches);
                    $allowed = array_map(static fn (string $token): string => trim($token, '{}'), $templates->availablePlaceholders($type));
                    foreach (array_unique($matches[1] ?? []) as $placeholder) {
                        if (!in_array($placeholder, $allowed, true)) $unknownPlaceholders[] = $type->name.': {'.$placeholder.'}';
                    }
                    unset($requiredCore);
                }
            });

            if ($invalidTypes > 0) {
                $this->line('<fg=red>FAIL</> '.$invalidTypes.' tipova artikla ima neispravnu konfiguraciju šablona.');
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Konfiguracije tipova artikla su validne.');
            }

            if ($unknownPlaceholders !== []) {
                $this->line('<fg=red>FAIL</> Nepoznate promenljive šablona: '.implode(', ', array_slice(array_unique($unknownPlaceholders), 0, 12)));
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Svi šabloni naziva koriste poznate promenljive.');
            }

            $invalidWeights = DB::table('product_type_fields')
                ->where(fn ($query) => $query->where('completeness_weight', '<', 1)->orWhere('completeness_weight', '>', 100))
                ->count();
            if ($invalidWeights > 0) {
                $this->line('<fg=red>FAIL</> '.$invalidWeights.' polja ima neispravnu težinu kompletnosti.');
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Težine kompletnosti su u dozvoljenom opsegu.');
            }

            $invalidCompleteness = DB::table('products')
                ->whereNull('completeness_percent')
                ->orWhere('completeness_percent', '<', 0)
                ->orWhere('completeness_percent', '>', 100)
                ->count();
            if ($invalidCompleteness > 0) {
                $this->line('<fg=red>FAIL</> '.$invalidCompleteness.' artikala ima neispravan procenat kompletnosti.');
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Procenat kompletnosti svih artikala je validan.');
            }

            $activeBelowMinimum = DB::table('products')
                ->join('product_types', 'product_types.id', '=', 'products.product_type_id')
                ->where('products.status', 'active')
                ->whereColumn('products.completeness_percent', '<', 'product_types.minimum_completeness_percent')
                ->count();
            if ($activeBelowMinimum > 0) {
                $this->line('<fg=red>FAIL</> '.$activeBelowMinimum.' aktivnih artikala je ispod minimuma kompletnosti.');
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Aktivni artikli ispunjavaju minimum kompletnosti.');
            }

            $invalidDefaults = DB::table('product_type_fields as pivot')
                ->join('specification_fields as fields', 'fields.id', '=', 'pivot.field_id')
                ->where('fields.data_type', 'select')
                ->whereNotNull('pivot.default_value')
                ->where('pivot.default_value', '!=', '')
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('specification_options as options')
                        ->whereColumn('options.field_id', 'pivot.field_id')
                        ->whereColumn('options.value', 'pivot.default_value')
                        ->where('options.status', 'active');
                })
                ->count();
            if ($invalidDefaults > 0) {
                $this->line('<fg=red>FAIL</> '.$invalidDefaults.' dropdown polja ima podrazumevanu vrednost koja nije aktivna opcija.');
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Podrazumevane dropdown vrednosti su validne.');
            }
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> SQL provera pametnih proizvoda nije uspela: '.$exception::class.': '.$exception->getMessage());
            $failed = true;
        }

        if ($failed) {
            $this->warn('Pokreni: php artisan app:smart-products-doctor --repair');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
