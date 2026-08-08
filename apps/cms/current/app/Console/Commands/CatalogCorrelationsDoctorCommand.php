<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductType;
use App\Models\SpecificationField;
use App\Models\SpecificationOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class CatalogCorrelationsDoctorCommand extends Command
{
    protected $signature = 'app:catalog-correlations-doctor {--repair : Pokreni migracije pre provere}';
    protected $description = 'Proveri zavisne specifikacije, brend/linija korelacije i strukturirani izbor procesora';

    public function handle(): int
    {
        if ($this->option('repair')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                $this->output->write(Artisan::output());
            } catch (Throwable $exception) {
                $this->error('Repair nije uspeo: '.$exception->getMessage());
                return self::FAILURE;
            }
        }

        $failed = false;
        $requirements = [
            'specification_fields' => ['id', 'data_type', 'parent_field_id', 'detail_input_enabled', 'detail_label', 'detail_placeholder'],
            'specification_options' => ['id', 'field_id', 'label', 'value', 'status', 'sort_order'],
            'specification_option_dependencies' => ['parent_option_id', 'child_option_id', 'created_at'],
            'product_spec_values' => ['product_id', 'field_id', 'value_text', 'value_detail'],
            'product_lines' => ['id', 'brand_id', 'name', 'slug', 'status'],
        ];

        foreach ($requirements as $table => $columns) {
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
            $this->line('Pokreni: php artisan app:catalog-correlations-doctor --repair');
            return self::FAILURE;
        }

        try {
            $processor = SpecificationField::query()
                ->whereIn('slug', ['procesor', 'processor', 'cpu', 'cpu-procesor'])
                ->orWhereRaw('LOWER(name) IN (?, ?, ?)', ['procesor', 'processor', 'cpu'])
                ->orderBy('id')
                ->first();

            if ($processor === null || $processor->data_type !== 'select' || !$processor->detail_input_enabled) {
                $this->line('<fg=red>FAIL</> Procesor nije konfigurisan kao dropdown sa dodatnim poljem za tačan model.');
                $failed = true;
            } else {
                $requiredCpuFamilies = [
                    'Intel Core i5', 'Intel Core Ultra 5', 'AMD Ryzen 5', 'AMD Ryzen AI 9',
                    'Qualcomm Snapdragon X Elite', 'Apple M5 Pro', 'Ostalo / drugo',
                ];
                $available = SpecificationOption::query()
                    ->where('field_id', $processor->id)
                    ->where('status', 'active')
                    ->pluck('value')
                    ->all();
                $missingCpu = array_values(array_diff($requiredCpuFamilies, $available));
                if ($missingCpu !== []) {
                    $this->line('<fg=red>FAIL</> Nedostaju procesorske porodice: '.implode(', ', $missingCpu));
                    $failed = true;
                } else {
                    $this->line('<fg=green>PASS</> Dropdown procesora i polje za tačan model su spremni.');
                }
            }

            $mismatchedLines = Product::query()
                ->join('product_lines', 'product_lines.id', '=', 'products.product_line_id')
                ->whereNotNull('products.brand_id')
                ->whereColumn('products.brand_id', '!=', 'product_lines.brand_id')
                ->count();
            if ($mismatchedLines > 0) {
                $this->line('<fg=red>FAIL</> '.$mismatchedLines.' artikala ima liniju koja ne pripada izabranom brendu.');
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Svi artikli imaju usklađen brend i liniju proizvoda.');
            }

            $orphanDependencies = DB::table('specification_option_dependencies as dependencies')
                ->leftJoin('specification_options as parent_options', 'parent_options.id', '=', 'dependencies.parent_option_id')
                ->leftJoin('specification_options as child_options', 'child_options.id', '=', 'dependencies.child_option_id')
                ->leftJoin('specification_fields as child_fields', 'child_fields.id', '=', 'child_options.field_id')
                ->where(function ($query): void {
                    $query->whereNull('parent_options.id')
                        ->orWhereNull('child_options.id')
                        ->orWhereNull('child_fields.id')
                        ->orWhereNull('child_fields.parent_field_id')
                        ->orWhereColumn('child_fields.parent_field_id', '!=', 'parent_options.field_id');
                })
                ->count();
            if ($orphanDependencies > 0) {
                $this->line('<fg=red>FAIL</> Pronađeno je '.$orphanDependencies.' neispravnih veza između opcija.');
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Veze između roditeljskih i zavisnih opcija su konzistentne.');
            }

            $cycleFailed = false;
            $fields = SpecificationField::query()->get(['id', 'parent_field_id'])->keyBy('id');
            foreach ($fields as $field) {
                $visited = [];
                $cursor = $field;
                while ($cursor !== null && $cursor->parent_field_id !== null) {
                    if (isset($visited[$cursor->id])) {
                        $this->line('<fg=red>FAIL</> Kružna veza specifikacionih polja kod ID '.$field->id.'.');
                        $failed = true;
                        $cycleFailed = true;
                        break;
                    }
                    $visited[$cursor->id] = true;
                    $cursor = $fields->get((int) $cursor->parent_field_id);
                }
            }
            if (!$cycleFailed) $this->line('<fg=green>PASS</> Nema kružnih veza između specifikacionih polja.');

            $typesWithMissingParents = 0;
            ProductType::query()->with('fields:id,parent_field_id')->chunkById(100, function ($types) use (&$typesWithMissingParents): void {
                foreach ($types as $type) {
                    $ids = $type->fields->pluck('id')->map(fn ($id) => (int) $id)->all();
                    foreach ($type->fields as $field) {
                        if ($field->parent_field_id !== null && !in_array((int) $field->parent_field_id, $ids, true)) {
                            $typesWithMissingParents++;
                            break;
                        }
                    }
                }
            });
            if ($typesWithMissingParents > 0) {
                $this->line('<fg=red>FAIL</> '.$typesWithMissingParents.' tipova artikla ima zavisno polje bez roditeljskog polja.');
                $failed = true;
            } else {
                $this->line('<fg=green>PASS</> Svaki tip artikla sadrži roditeljska polja svojih zavisnih specifikacija.');
            }
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> SQL provera korelacija nije uspela: '.$exception::class.': '.$exception->getMessage());
            $failed = true;
        }

        if ($failed) {
            $this->line('Pokreni: php artisan app:catalog-correlations-doctor --repair');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
