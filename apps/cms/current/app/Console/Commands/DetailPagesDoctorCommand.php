<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\CatalogController;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View as LaravelView;
use Throwable;

final class DetailPagesDoctorCommand extends Command
{
    protected $signature = 'app:detail-pages-doctor
        {--order-id= : Konkretna porudžbina za proveru}
        {--product-id= : Konkretan artikal za proveru}';

    protected $description = 'Renderuj ključne pojedinačne stranice porudžbine i artikla bez browsera';

    public function handle(): int
    {
        $orderArguments = ['--render' => true];
        $orderId = (int) ($this->option('order-id') ?? 0);
        if ($orderId > 0) {
            $orderArguments['--order-id'] = $orderId;
        }

        $failed = false;
        $ordersExit = Artisan::call('app:orders-doctor', $orderArguments);
        $this->output->write(Artisan::output());
        if ($ordersExit !== self::SUCCESS) {
            $failed = true;
        }

        $actor = User::query()
            ->where('users.status', 'active')
            ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
            ->with('role')
            ->orderBy('users.id')
            ->first();

        if (!$actor instanceof User) {
            $this->line('<fg=red>FAIL</> Nema aktivnog SuperAdministratora za detail page audit.');
            return self::FAILURE;
        }

        $productQuery = Product::query()->with('images')->orderBy('id');
        $productId = (int) ($this->option('product-id') ?? 0);
        if ($productId > 0) {
            $productQuery->whereKey($productId);
        } else {
            $productQuery->where('status', 'active');
        }
        $product = $productQuery->first();

        if (!$product instanceof Product) {
            if ($productId > 0) {
                $this->line('<fg=red>FAIL</> Traženi artikal ne postoji.');
                return self::FAILURE;
            }
            $this->line('<fg=yellow>SKIP</> Nema aktivnog artikla za detail page audit.');
            return $failed ? self::FAILURE : self::SUCCESS;
        }

        $application = app();
        $previousRequest = $application->bound('request') ? $application->make('request') : null;
        $previousUser = Auth::guard()->user();

        try {
            $this->request('/catalog/'.$product->slug, $actor);
            $catalogView = app()->call([app(CatalogController::class), 'show'], [
                'request' => app('request'),
                'slug' => (string) $product->slug,
            ]);
            if (!$this->assertView($catalogView, 'data-catalog-detail-ready="1"', 'Korisnički detalj artikla')) {
                $failed = true;
            }

            $this->request('/admin/catalog/'.$product->id.'/edit', $actor);
            $editView = app()->call([app(AdminProductController::class), 'edit'], [
                'product' => $product->fresh() ?? $product,
            ]);
            if (!$this->assertView($editView, 'data-product-edit-ready="1"', 'Administratorska izmena artikla')) {
                $failed = true;
            }

            $this->request('/admin/catalog/'.$product->id.'/images', $actor);
            $imagesView = app()->call([app(ProductImageController::class), 'index'], [
                'product' => $product->fresh() ?? $product,
            ]);
            if (!$this->assertView($imagesView, 'data-product-images-ready="1"', 'Administratorska galerija artikla')) {
                $failed = true;
            }

            if ($failed) {
                $this->line('<fg=red>FAIL</> Jedna ili više ključnih pojedinačnih stranica nisu prošle render proveru.');
                return self::FAILURE;
            }

            $this->line('<fg=green>PASS</> Sve ključne pojedinačne stranice su uspešno renderovane.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> Detail page audit nije uspeo: '.$exception::class.': '.$exception->getMessage());
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
            try {
                $session->put('_token', Str::random(40));
            } catch (Throwable) {
                // Pojedinačni render će prijaviti konkretan session problem.
            }
        }

        $request = Request::create($path, 'GET');
        $request->setUserResolver(static fn (): User => $actor);
        $request->setLaravelSession($session);
        app()->instance('request', $request);
        View::share('errors', new ViewErrorBag());

        return $request;
    }

    private function assertView(mixed $view, string $marker, string $label): bool
    {
        if (!$view instanceof LaravelView) {
            $this->line('<fg=red>FAIL</> '.$label.' nije vratio Laravel View.');
            return false;
        }

        $html = $view->with('errors', new ViewErrorBag())->render();
        if (!str_contains($html, $marker)) {
            $this->line('<fg=red>FAIL</> '.$label.' nema očekivani readiness marker.');
            return false;
        }

        $this->line('<fg=green>PASS</> '.$label.' je uspešno renderovan.');
        return true;
    }
}
