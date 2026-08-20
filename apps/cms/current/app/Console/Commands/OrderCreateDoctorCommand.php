<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\OrderController;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View as LaravelView;
use Throwable;

final class OrderCreateDoctorCommand extends Command
{
    protected $signature = 'app:order-create-doctor {--render : Renderuj /order/new bez browsera}';

    protected $description = 'Proveri podatke, rute i Blade render stranice za kreiranje porudžbine';

    public function handle(): int
    {
        $failed = false;

        foreach (['users', 'roles', 'products', 'bank_accounts'] as $table) {
            if (Schema::hasTable($table)) {
                $this->line('<fg=green>PASS</> '.$table.' je spremna.');
            } else {
                $this->line('<fg=red>FAIL</> Nedostaje tabela '.$table.'.');
                $failed = true;
            }
        }

        foreach (['orders.create', 'orders.store'] as $name) {
            if (Route::has($name)) {
                $this->line('<fg=green>PASS</> Ruta '.$name.' je registrovana.');
            } else {
                $this->line('<fg=red>FAIL</> Ruta '.$name.' nije registrovana.');
                $failed = true;
            }
        }

        if ($failed || !$this->option('render')) {
            if (!$this->option('render') && !$failed) {
                $this->line('INFO Za punu proveru pokreni: php artisan app:order-create-doctor --render');
            }
            return $failed ? self::FAILURE : self::SUCCESS;
        }

        $actor = User::query()
            ->where('users.status', 'active')
            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))
            ->with(['role', 'group'])
            ->orderBy('users.id')
            ->first();

        if (!$actor instanceof User) {
            $this->line('<fg=red>FAIL</> Nema aktivnog Administratora za render /order/new.');
            return self::FAILURE;
        }

        $application = app();
        $previousRequest = $application->bound('request') ? $application->make('request') : null;
        $previousUser = Auth::guard()->user();

        try {
            Auth::guard()->setUser($actor);
            $session = $application->make('session')->driver();
            try {
                $session->start();
                if (!is_string($session->token()) || $session->token() === '') {
                    $session->regenerateToken();
                }
            } catch (Throwable) {
                $session->put('_token', Str::random(40));
            }

            $request = Request::create('/order/new', 'GET');
            $request->setUserResolver(static fn (): User => $actor);
            $request->setLaravelSession($session);
            $application->instance('request', $request);
            View::share('errors', new ViewErrorBag());

            $view = $application->call([$application->make(OrderController::class), 'create'], [
                'request' => $request,
            ]);

            if (!$view instanceof LaravelView) {
                $this->line('<fg=red>FAIL</> /order/new nije vratio Laravel View.');
                return self::FAILURE;
            }

            $html = $view->with('errors', new ViewErrorBag())->render();
            foreach (['data-order-create-ready="1"', 'name="idempotency_key"', 'data-order-product'] as $marker) {
                if (!str_contains($html, $marker)) {
                    $this->line('<fg=red>FAIL</> /order/new nema očekivani marker: '.$marker);
                    return self::FAILURE;
                }
            }

            $this->line('<fg=green>PASS</> /order/new i kompletan authenticated layout su uspešno renderovani.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> /order/new render nije uspeo: '.$exception::class.': '.$exception->getMessage());
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
}
