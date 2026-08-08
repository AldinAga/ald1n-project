<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;

final class CmsV215DoctorCommand extends Command
{
    private const DESKTOP_TYPE_SLUG = 'desktop-racunar';
    private const POWER_FIELD_SLUG = 'snaga-napajanja';

    protected $signature = 'app:cms-v2-1-5-doctor
        {--render : Kompajliraj sve Blade prikaze i renderuj sistemske stranice grešaka}
        {--repair : Poveži i postavi polje snage napajanja u sredinu specifikacija desktop računara}';

    protected $description = 'Proveri v2.1.5 UX runtime, mobilne akcije, pristupačnost, ključne rute i raspored specifikacija računara.';

    public function handle(): int
    {
        $failed = false;
        $warned = false;

        foreach ([
            public_path('assets/css/app.css'),
            public_path('assets/js/ux-runtime.js'),
            resource_path('views/layouts/app.blade.php'),
            resource_path('views/errors/minimal.blade.php'),
            resource_path('views/errors/404.blade.php'),
            resource_path('views/errors/500.blade.php'),
            resource_path('views/admin/products/form.blade.php'),
            resource_path('views/orders/create.blade.php'),
            database_path('migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php'),
        ] as $path) {
            if (!is_file($path) || filesize($path) === 0) {
                $this->error('FAIL Nedostaje release fajl '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path).'.');
                $failed = true;
            }
        }
        if (!$failed) {
            $this->info('PASS v2.1.5 UX i migration fajlovi postoje.');
        }

        $css = (string) @file_get_contents(public_path('assets/css/app.css'));
        $js = (string) @file_get_contents(public_path('assets/js/ux-runtime.js'));
        $layout = (string) @file_get_contents(resource_path('views/layouts/app.blade.php'));

        foreach ([
            'ux-mobile-action-dock',
            '--ux-touch-target:46px',
            '.has-field-error',
            '.error-page-card',
            '@media(max-width:820px)',
            'prefers-reduced-motion',
        ] as $needle) {
            if (!str_contains($css, $needle)) {
                $this->error('FAIL CSS nema v2.1.5 marker '.$needle.'.');
                $failed = true;
            }
        }
        foreach ([
            'createMobileActionDock',
            'protectForms',
            'protectUnsavedChanges',
            'improveAlerts',
            'improveFields',
            'addKeyboardSave',
            'aria-busy',
        ] as $needle) {
            if (!str_contains($js, $needle)) {
                $this->error('FAIL UX runtime nema '.$needle.'.');
                $failed = true;
            }
        }
        if (!str_contains($layout, "assets/js/ux-runtime.js") || !str_contains($layout, "@stack('scripts')")) {
            $this->error('FAIL Glavni layout ne učitava UX runtime pre page skripti.');
            $failed = true;
        }
        if (!$failed) {
            $this->info('PASS Mobilni dock, zaštita formulara, greške i pristupačnost su uključeni.');
        }

        foreach ([
            'dashboard',
            'catalog.index',
            'orders.create',
            'account.show',
            'notifications.index',
            'admin.products.index',
            'admin.products.create',
            'admin.dictionary.product-type',
        ] as $routeName) {
            if (!Route::has($routeName)) {
                $this->error('FAIL Nedostaje ključna ruta '.$routeName.'.');
                $failed = true;
            }
        }

        $routeNames = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (!is_string($name) || $name === '') {
                continue;
            }
            if (isset($routeNames[$name])) {
                $this->error('FAIL Duplirano ime rute '.$name.'.');
                $failed = true;
            }
            $routeNames[$name] = true;
        }
        if (!$failed) {
            $this->info('PASS Ključne rute postoje i nema dupliranih imena ruta.');
        }

        try {
            $auditedRouteActions = $this->auditRouteActions();
            $this->info('PASS Route action audit: '.$auditedRouteActions.' controller akcija postoji.');
        } catch (Throwable $exception) {
            $this->error('FAIL Route action audit: '.$exception->getMessage());
            $failed = true;
        }

        try {
            $powerState = $this->desktopPowerSupplyState();
            if ($powerState['error'] !== null) {
                $this->error('FAIL '.$powerState['error']);
                $failed = true;
            } elseif (!$powerState['active'] || !$powerState['assigned'] || !$powerState['in_middle']) {
                if ($this->option('repair')) {
                    $this->activateAndPlaceDesktopPowerSupplyField((int) $powerState['product_type_id'], (int) $powerState['field_id']);
                    $powerState = $this->desktopPowerSupplyState();
                    if (!$powerState['active'] || !$powerState['assigned'] || !$powerState['in_middle']) {
                        $this->error('FAIL Polje Snaga napajanja nije moglo da se postavi u sredinu specifikacija desktop računara.');
                        $failed = true;
                    } else {
                        $this->info('PASS Polje Snaga napajanja je povezano i postavljeno u srednju zonu specifikacija desktop računara.');
                    }
                } else {
                    $this->line('<fg=yellow>WARN</> Polje Snaga napajanja nije aktivno, povezano ili nije u srednjoj zoni. Pokreni komandu sa --repair.');
                    $warned = true;
                }
            } else {
                $this->info('PASS Polje Snaga napajanja je u srednjoj zoni specifikacija desktop računara.');
            }
        } catch (Throwable $exception) {
            $this->error('FAIL Provera rasporeda snage napajanja: '.$exception::class.': '.$exception->getMessage());
            $failed = true;
        }

        if ($this->option('render')) {
            try {
                $compiled = $this->compileAllBladeViews();
                $rendered = $this->renderErrorPages();
                $this->info('PASS Blade compile: '.$compiled.' prikaza; render sistemskih grešaka: '.$rendered.' stranica.');
            } catch (Throwable $exception) {
                $this->error('FAIL Blade/runtime provera: '.$exception::class.': '.$exception->getMessage());
                $failed = true;
            }
        } else {
            $this->line('SKIP Blade compile/render nije tražen. Pokreni sa --render za punu proveru.');
        }

        if ($failed) {
            $this->error('v2.1.5 nije spreman.');
            return self::FAILURE;
        }

        if ($warned) {
            $this->line('<fg=yellow>v2.1.5 radi, ali zahteva navedeno usklađivanje.</>');
            return self::SUCCESS;
        }

        $this->info('v2.1.5 UX, mobilni prikaz, runtime stabilnost i specifikacije računara su spremni.');
        return self::SUCCESS;
    }


    private function auditRouteActions(): int
    {
        $count = 0;

        foreach (Route::getRoutes() as $route) {
            $controller = $route->getAction('controller');
            if (!is_string($controller) || $controller === '' || $controller === 'Closure') {
                continue;
            }

            if (str_contains($controller, '@')) {
                [$class, $method] = explode('@', $controller, 2);
            } else {
                $class = $controller;
                $method = '__invoke';
            }

            if (!class_exists($class)) {
                throw new RuntimeException(sprintf(
                    'Ruta %s koristi nepostojeći controller %s.',
                    $route->uri(),
                    $class,
                ));
            }

            if (!method_exists($class, $method)) {
                throw new RuntimeException(sprintf(
                    'Ruta %s koristi nepostojeću akciju %s@%s.',
                    $route->uri(),
                    $class,
                    $method,
                ));
            }

            $count++;
        }

        return $count;
    }

    /** @return array{error:?string,product_type_id:?int,field_id:?int,active:bool,assigned:bool,in_middle:bool} */
    private function desktopPowerSupplyState(): array
    {
        foreach (['product_types', 'specification_fields', 'product_type_fields'] as $table) {
            if (!Schema::hasTable($table)) {
                return [
                    'error' => 'Nedostaje tabela '.$table.'. Pokreni php artisan migrate --force.',
                    'product_type_id' => null,
                    'field_id' => null,
                    'active' => false,
                    'assigned' => false,
                    'in_middle' => false,
                ];
            }
        }

        $productTypeId = DB::table('product_types')->where('slug', self::DESKTOP_TYPE_SLUG)->value('id');
        if ($productTypeId === null) {
            return [
                'error' => 'Ne postoji tip proizvoda sa slugom '.self::DESKTOP_TYPE_SLUG.'.',
                'product_type_id' => null,
                'field_id' => null,
                'active' => false,
                'assigned' => false,
                'in_middle' => false,
            ];
        }

        $field = DB::table('specification_fields')->where('slug', self::POWER_FIELD_SLUG)->first(['id', 'status']);
        $fieldId = $field?->id;
        if ($fieldId === null) {
            return [
                'error' => 'Ne postoji definisano polje specifikacije sa slugom '.self::POWER_FIELD_SLUG.'.',
                'product_type_id' => (int) $productTypeId,
                'field_id' => null,
                'active' => false,
                'assigned' => false,
                'in_middle' => false,
            ];
        }

        $ids = DB::table('product_type_fields')
            ->where('product_type_id', (int) $productTypeId)
            ->orderBy('sort_order')
            ->orderBy('field_id')
            ->pluck('field_id')
            ->map(static fn ($id): int => (int) $id)
            ->values();

        $index = $ids->search((int) $fieldId, true);
        $assigned = $index !== false;
        $count = $ids->count();
        $target = $count > 0 ? ($count - 1) / 2 : 0.0;
        $tolerance = max(1.0, ceil($count * 0.2));
        $inMiddle = $assigned && abs((float) $index - $target) <= $tolerance;

        return [
            'error' => null,
            'product_type_id' => (int) $productTypeId,
            'field_id' => (int) $fieldId,
            'active' => (string) ($field->status ?? '') === 'active',
            'assigned' => $assigned,
            'in_middle' => $inMiddle,
        ];
    }

    private function activateAndPlaceDesktopPowerSupplyField(int $productTypeId, int $fieldId): void
    {
        DB::transaction(function () use ($productTypeId, $fieldId): void {
            if (Schema::hasColumn('specification_fields', 'status')) {
                DB::table('specification_fields')->where('id', $fieldId)->update(['status' => 'active']);
            }

            $assignedIds = DB::table('product_type_fields')
                ->where('product_type_id', $productTypeId)
                ->orderBy('sort_order')
                ->orderBy('field_id')
                ->pluck('field_id')
                ->map(static fn ($id): int => (int) $id)
                ->reject(static fn (int $id): bool => $id === $fieldId)
                ->values()
                ->all();

            $exists = DB::table('product_type_fields')
                ->where('product_type_id', $productTypeId)
                ->where('field_id', $fieldId)
                ->exists();

            if (!$exists) {
                DB::table('product_type_fields')->insert($this->onlyExistingPivotColumns([
                    'product_type_id' => $productTypeId,
                    'field_id' => $fieldId,
                    'is_required' => false,
                    'is_filterable' => true,
                    'show_in_summary' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'default_value' => null,
                    'default_detail' => null,
                    'completeness_weight' => 1,
                    'include_in_name' => false,
                ]));
            }

            $middleIndex = intdiv(count($assignedIds) + 1, 2);
            array_splice($assignedIds, $middleIndex, 0, [$fieldId]);

            foreach ($assignedIds as $index => $assignedFieldId) {
                DB::table('product_type_fields')
                    ->where('product_type_id', $productTypeId)
                    ->where('field_id', $assignedFieldId)
                    ->update(['sort_order' => ($index + 1) * 10]);
            }
        });
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function onlyExistingPivotColumns(array $data): array
    {
        return array_filter(
            $data,
            static fn (string $column): bool => Schema::hasColumn('product_type_fields', $column),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function compileAllBladeViews(): int
    {
        $root = resource_path('views');
        if (!is_dir($root)) {
            throw new RuntimeException('Direktorijum resources/views ne postoji.');
        }

        $count = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            if (!is_string($source)) {
                throw new RuntimeException('Ne mogu da pročitam '.$file->getPathname().'.');
            }
            Blade::compileString($source);
            $count++;
        }

        return $count;
    }

    private function renderErrorPages(): int
    {
        View::share('errors', new ViewErrorBag());
        $count = 0;
        foreach ([403, 404, 419, 429, 500, 503] as $status) {
            $html = view('errors.'.$status)->with('errors', new ViewErrorBag())->render();
            if (!str_contains($html, (string) $status) || !str_contains($html, 'error-page-card')) {
                throw new RuntimeException('Sistemska stranica '.$status.' nema očekivani sadržaj.');
            }
            $count++;
        }
        return $count;
    }
}
