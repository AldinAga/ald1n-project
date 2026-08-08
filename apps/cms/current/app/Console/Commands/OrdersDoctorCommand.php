<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\OrderController as UserOrderController;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderAccessService;
use App\Services\OrderIndexService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Throwable;

final class OrdersDoctorCommand extends Command
{
    protected $signature = 'app:orders-doctor
        {--repair : Pokreni migracije i CoreAccessSeeder}
        {--render : Renderuj listu i detaljne stranice porudžbine koje koristi browser}
        {--order-id= : Konkretna porudžbina za admin i korisnički detail render}';

    protected $description = 'Proveri šemu, SQL i kompletan render liste i detalja porudžbina';

    public function handle(OrderIndexService $orders, OrderAccessService $access): int
    {
        if ($this->option('repair') && !$this->repair()) {
            return self::FAILURE;
        }

        $issues = $orders->readinessIssues();
        if ($issues !== []) {
            foreach ($issues as $issue) {
                $this->line('<fg=red>FAIL</> '.$issue);
            }
            $this->line('Pokreni: php artisan app:orders-doctor --repair --render');
            return self::FAILURE;
        }
        $this->line('<fg=green>PASS</> Orders šema je kompletna.');

        $actor = $this->adminActor();
        if (!$actor instanceof User) {
            $this->line('<fg=red>FAIL</> Nema aktivnog Administratora za proveru.');
            return self::FAILURE;
        }

        try {
            $page = $orders->paginate($actor, [], $access, 5);
            $counts = $orders->attentionCounts($actor, $access);
            $suppliers = $orders->suppliers($actor);
            $this->line('<fg=green>PASS</> Orders SQL upiti su uspešni.');
            $this->line(sprintf(
                'Korisnik #%d, prvi page=%d, ukupno=%d, čeka=%d, prekoračeno=%d, odgovorna lica=%d.',
                $actor->id,
                $page->count(),
                $page->total(),
                $counts['unaccepted'],
                $counts['overdue'],
                $suppliers->count(),
            ));
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> Orders SQL nije uspeo: '.$exception::class.': '.$exception->getMessage());
            return self::FAILURE;
        }

        if (!$this->option('render')) {
            $this->line('INFO Za kompletan browser render pokreni: php artisan app:orders-doctor --render --order-id=1');
            return self::SUCCESS;
        }

        $application = app();
        $previousRequest = $application->bound('request') ? $application->make('request') : null;
        $previousUser = Auth::guard()->user();

        try {
            $failed = false;

            if (!$this->renderAdminIndex($actor, $orders, $access)) {
                $failed = true;
            }

            $order = $this->targetOrder($actor, $access);
            if (!$order instanceof Order) {
                if ($this->option('order-id') !== null) {
                    $this->line('<fg=red>FAIL</> Tražena porudžbina ne postoji ili Administrator nema pristup.');
                    return self::FAILURE;
                }
                $this->line('<fg=yellow>SKIP</> Nema porudžbina za detail render proveru.');
                return $failed ? self::FAILURE : self::SUCCESS;
            }

            if (!$this->renderAdminDetail($actor, $order)) {
                $failed = true;
            }

            if (!$this->renderUserDetail($order)) {
                $failed = true;
            }

            return $failed ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> Orders render nije uspeo: '.$exception::class.': '.$exception->getMessage());
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

    private function repair(): bool
    {
        try {
            $exit = Artisan::call('migrate', ['--force' => true]);
            $this->output->write(Artisan::output());
            if ($exit !== self::SUCCESS) {
                return false;
            }
            Artisan::call('db:seed', ['--class' => CoreAccessSeeder::class, '--force' => true]);
            $this->output->write(Artisan::output());
            return true;
        } catch (Throwable $exception) {
            $this->error('Repair nije uspeo: '.$exception::class.': '.$exception->getMessage());
            return false;
        }
    }

    private function adminActor(): ?User
    {
        return User::query()
            ->where('users.status', 'active')
            ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))
            ->with('role')
            ->orderByRaw("CASE WHEN roles.slug = 'superadmin' THEN 0 ELSE 1 END")
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->select('users.*')
            ->orderBy('users.id')
            ->first();
    }

    private function targetOrder(User $actor, OrderAccessService $access): ?Order
    {
        $query = Order::query()->orderBy('id');
        $access->applyManagedScope($query, $actor);

        $id = (int) ($this->option('order-id') ?? 0);
        if ($id > 0) {
            $query->whereKey($id);
        }

        return $query->first();
    }

    private function renderAdminIndex(User $actor, OrderIndexService $orders, OrderAccessService $access): bool
    {
        $request = $this->request('/admin/orders', $actor);
        $response = app(OrderController::class)->index($request, $orders, $access);

        return $this->assertResponse(
            $response,
            'data-orders-page-ready="1"',
            'Administratorska lista porudžbina',
        );
    }

    private function renderAdminDetail(User $actor, Order $order): bool
    {
        $request = $this->request('/admin/orders/'.$order->id, $actor);
        $controller = app(OrderController::class);
        $response = app()->call([$controller, 'show'], [
            'request' => $request,
            'order' => $order->fresh() ?? $order,
        ]);

        if ($response instanceof Response && $response->headers->get('X-Ald1n-Detail-Fallback') === '1') {
            $this->line('<fg=red>FAIL</> Administratorski detalj porudžbine #'.$order->id.' koristi fallback prikaz.');
            $exception = $controller->lastDetailRenderException();
            if ($exception instanceof Throwable) {
                $this->line('<fg=yellow>UZROK</> '.$exception::class.': '.$exception->getMessage());
                $this->line('Lokacija: '.$exception->getFile().':'.$exception->getLine());
            }
            $this->printDiagnostic($response);
            return false;
        }

        return $this->assertResponse(
            $response,
            'data-order-detail-ready="1"',
            'Administratorski detalj porudžbine #'.$order->id,
        );
    }

    private function renderUserDetail(Order $order): bool
    {
        $owner = User::query()->with('role')->find($order->user_id);
        if (!$owner instanceof User) {
            $this->line('<fg=red>FAIL</> Porudžbina #'.$order->id.' nema postojećeg vlasnika za korisnički detail render.');
            return false;
        }

        $request = $this->request('/orders/'.$order->id, $owner);
        $controller = app(UserOrderController::class);
        $response = app()->call([$controller, 'show'], [
            'request' => $request,
            'order' => $order->fresh() ?? $order,
        ]);

        if ($response instanceof Response && $response->headers->get('X-Ald1n-Detail-Fallback') === '1') {
            $this->line('<fg=red>FAIL</> Korisnički detalj porudžbine #'.$order->id.' koristi fallback prikaz.');
            $exception = $controller->lastDetailRenderException();
            if ($exception instanceof Throwable) {
                $this->line('<fg=yellow>UZROK</> '.$exception::class.': '.$exception->getMessage());
                $this->line('Lokacija: '.$exception->getFile().':'.$exception->getLine());
            }
            $this->printDiagnostic($response);
            return false;
        }

        return $this->assertResponse(
            $response,
            'data-order-user-detail-ready="1"',
            'Korisnički detalj porudžbine #'.$order->id,
        );
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
                // Render će prijaviti konkretan session problem ako postoji.
            }
        }

        $request = Request::create($path, 'GET');
        $request->setUserResolver(static fn (): User => $actor);
        $request->setLaravelSession($session);
        app()->instance('request', $request);
        View::share('errors', new ViewErrorBag());

        return $request;
    }

    private function assertResponse(mixed $response, string $marker, string $label): bool
    {
        if (!$response instanceof Response) {
            $this->line('<fg=red>FAIL</> '.$label.' nije vratio HTTP odgovor.');
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            $this->line('<fg=red>FAIL</> '.$label.' je vratio HTTP '.$response->getStatusCode().'.');
            $this->printDiagnostic($response);
            return false;
        }

        if ($response->headers->get('X-Ald1n-Detail-Fallback') === '1') {
            $this->line('<fg=red>FAIL</> '.$label.' koristi bezbedni fallback umesto punog prikaza.');
            $this->printDiagnostic($response);
            return false;
        }

        if (!str_contains((string) $response->getContent(), $marker)) {
            $this->line('<fg=red>FAIL</> '.$label.' nema očekivani readiness marker.');
            return false;
        }

        $this->line('<fg=green>PASS</> '.$label.' je uspešno renderovan.');
        return true;
    }

    private function printDiagnostic(Response $response): void
    {
        $encoded = trim((string) $response->headers->get('X-Ald1n-Diagnostic', ''));
        if ($encoded !== '') {
            $padding = strlen($encoded) % 4;
            if ($padding > 0) {
                $encoded .= str_repeat('=', 4 - $padding);
            }
            $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
            $payload = is_string($decoded) ? json_decode($decoded, true) : null;
            if (is_array($payload)) {
                $exception = trim((string) ($payload['exception'] ?? ''));
                $message = trim((string) ($payload['message'] ?? ''));
                if ($exception !== '' || $message !== '') {
                    $this->line('<fg=yellow>DIAGNOSTIKA</> '.trim($exception.($message !== '' ? ': '.$message : '')));
                }
            }
        }

        $incident = trim((string) $response->headers->get('X-Ald1n-Incident', ''));
        if ($incident !== '') {
            $this->line('<fg=yellow>INFO</> Incident: '.$incident);
        }
    }
}
