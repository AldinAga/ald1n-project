<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\CatalogController;
use App\Models\Product;
use App\Models\User;
use App\Services\CatalogAccessService;
use Illuminate\Console\Command;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View as LaravelView;
use Throwable;

final class CatalogOwnershipDoctorCommand extends Command
{
    protected $signature = 'app:catalog-ownership-doctor {--render : Renderuj jedinstveni katalog i proveri legacy redirect}';

    protected $description = 'Proveri jedinstveni katalog i pravilo da Administrator uređuje samo svoje artikle';

    public function handle(CatalogAccessService $access): int
    {
        if (!Schema::hasTable('products')) {
            $this->line('<fg=red>FAIL</> Tabela products ne postoji.');
            return self::FAILURE;
        }

        $columns = Schema::getColumnListing('products');
        foreach (['id', 'created_by', 'updated_by', 'status', 'deleted_at'] as $column) {
            if (!in_array($column, $columns, true)) {
                $this->line('<fg=red>FAIL</> products nema kolonu '.$column.'.');
                return self::FAILURE;
            }
        }
        $this->line('<fg=green>PASS</> Products ownership kolone su spremne.');

        foreach (['catalog.index', 'catalog.show', 'admin.products.index', 'admin.products.edit'] as $route) {
            if (!Route::has($route)) {
                $this->line('<fg=red>FAIL</> Ruta '.$route.' nije registrovana.');
                return self::FAILURE;
            }
        }
        $this->line('<fg=green>PASS</> Unified catalog i legacy admin rute su registrovane.');

        $superadmin = User::query()
            ->where('users.status', 'active')
            ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
            ->with(['role', 'group'])
            ->orderBy('users.id')
            ->first();
        if (!$superadmin instanceof User) {
            $this->line('<fg=red>FAIL</> Nema aktivnog SuperAdministratora za ownership audit.');
            return self::FAILURE;
        }

        $product = Product::query()->orderBy('id')->first();
        if ($product instanceof Product && !$access->canManage($product, $superadmin)) {
            $this->line('<fg=red>FAIL</> SuperAdministrator nema pravo upravljanja artiklom #'.$product->id.'.');
            return self::FAILURE;
        }
        $this->line('<fg=green>PASS</> SuperAdministrator može da upravlja svim artiklima.');

        $admin = User::query()
            ->where('users.status', 'active')
            ->whereHas('role', static fn ($query) => $query->where('slug', 'admin'))
            ->with(['role', 'group'])
            ->orderBy('users.id')
            ->first();
        if ($admin instanceof User) {
            $own = Product::query()->where('created_by', $admin->id)->orderBy('id')->first();
            $foreign = Product::query()->where(static function ($query) use ($admin): void {
                $query->whereNull('created_by')->orWhere('created_by', '!=', $admin->id);
            })->orderBy('id')->first();

            if ($own instanceof Product && !$access->canManage($own, $admin)) {
                $this->line('<fg=red>FAIL</> Administrator ne može da uređuje svoj artikal #'.$own->id.'.');
                return self::FAILURE;
            }
            if ($foreign instanceof Product && $access->canManage($foreign, $admin)) {
                $this->line('<fg=red>FAIL</> Administrator može da uređuje tuđi artikal #'.$foreign->id.'.');
                return self::FAILURE;
            }
            if ($foreign instanceof Product && $access->canViewInUnifiedCatalog($foreign, $admin)) {
                $this->line('<fg=red>FAIL</> Administrator vidi tuđi artikal #'.$foreign->id.' u jedinstvenom katalogu.');
                return self::FAILURE;
            }
            if ($own instanceof Product && !$access->canViewInUnifiedCatalog($own, $admin)) {
                $this->line('<fg=red>FAIL</> Administrator ne vidi svoj artikal #'.$own->id.' u jedinstvenom katalogu.');
                return self::FAILURE;
            }
            $this->line('<fg=green>PASS</> Administrator vidi i uređuje isključivo artikle čiji je created_by njegov ID.');
        } else {
            $this->line('<fg=green>PASS</> Ownership pravilo je aktivno; trenutno nema aktivnog Administratora za data primer.');
        }

        $withoutOwner = Product::query()->whereNull('created_by')->count();
        $this->line('<fg=green>PASS</> Artikli bez vlasnika su SuperAdministrator-only: '.$withoutOwner.'.');

        if (!$this->option('render')) {
            $this->line('INFO Za render proveru pokreni: php artisan app:catalog-ownership-doctor --render');
            return self::SUCCESS;
        }

        $application = app();
        $previousRequest = $application->bound('request') ? $application->make('request') : null;
        $previousUser = Auth::guard()->user();

        try {
            $request = $this->request('/catalog', $superadmin);
            $view = $application->call([$application->make(CatalogController::class), 'index'], ['request' => $request]);
            if (!$view instanceof LaravelView) {
                $this->line('<fg=red>FAIL</> Unified katalog nije vratio Laravel View.');
                return self::FAILURE;
            }
            $html = $view->with('errors', new ViewErrorBag())->render();
            if (!str_contains($html, 'data-unified-catalog-ready="1"')) {
                $this->line('<fg=red>FAIL</> Unified katalog nema readiness marker.');
                return self::FAILURE;
            }
            $this->line('<fg=green>PASS</> Unified katalog i authenticated layout su uspešno renderovani.');

            $legacyRequest = $this->request('/admin/catalog', $superadmin);
            $redirect = $application->call([$application->make(AdminProductController::class), 'index'], ['request' => $legacyRequest]);
            if (!$redirect instanceof RedirectResponse || !str_contains($redirect->getTargetUrl(), '/catalog')) {
                $this->line('<fg=red>FAIL</> /admin/catalog ne preusmerava na jedinstveni katalog.');
                return self::FAILURE;
            }
            $this->line('<fg=green>PASS</> /admin/catalog kompatibilno preusmerava na /catalog.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> Catalog ownership render nije uspeo: '.$exception::class.': '.$exception->getMessage());
            $this->line('Lokacija: '.$exception->getFile().':'.$exception->getLine());
            return self::FAILURE;
        } finally {
            if ($previousUser instanceof User) {
                Auth::guard()->setUser($previousUser);
            } else {
                Auth::guard()->forgetUser();
            }
            if ($previousRequest instanceof Request) {
                $application->instance('request', $previousRequest);
            }
        }
    }

    private function request(string $path, User $actor): Request
    {
        Auth::guard()->setUser($actor);
        $session = app('session')->driver();
        try {
            $session->start();
            if (!is_string($session->token()) || $session->token() === '') {
                $session->regenerateToken();
            }
        } catch (Throwable) {
            $session->put('_token', Str::random(40));
        }

        $request = Request::create($path, 'GET');
        $request->setUserResolver(static fn (): User => $actor);
        $request->setLaravelSession($session);
        app()->instance('request', $request);
        View::share('errors', new ViewErrorBag());

        return $request;
    }
}
