<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\DashboardController;
use App\Models\User;
use App\Services\LegacyUserRecoveryService;
use App\Services\UserLoginResolver;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Throwable;

final class AuthDoctorCommand extends Command
{
    protected $signature = 'app:auth-doctor
        {login? : Korisničko ime ili e-mail za proveru}
        {--render-dashboard : Renderuj isti dashboard koji se otvara odmah nakon prijave}';
    protected $description = 'Proveri korisnike i uslove za prijavu bez prikazivanja lozinki ili hash vrednosti';

    public function handle(UserLoginResolver $users, LegacyUserRecoveryService $recovery): int
    {
        $failed = false;

        foreach ([
            'session' => storage_path('framework/sessions'),
            'cache' => storage_path('framework/cache/data'),
            'compiled views' => storage_path('framework/views'),
            'logs' => storage_path('logs'),
        ] as $label => $directory) {
            if (is_dir($directory) && is_writable($directory)) {
                $this->pass($label.' direktorijum je upisiv: '.$directory);
            } else {
                $this->failLine($label.' direktorijum ne postoji ili nije upisiv: '.$directory);
                $failed = true;
            }
        }

        try {
            $targetCount = DB::table('users')->count();
            $this->pass('Laravel baza dostupna; korisnika: '.$targetCount);
        } catch (Throwable $exception) {
            $this->failLine('Laravel baza nije dostupna: '.$exception->getMessage());
            return self::FAILURE;
        }

        try {
            $legacyCount = DB::connection('legacy')->table('users')->count();
            $this->pass('Legacy baza dostupna; korisnika: '.$legacyCount);
        } catch (Throwable $exception) {
            $this->failLine('Legacy baza nije dostupna: '.$exception->getMessage());
            $failed = true;
        }

        $login = trim((string) ($this->argument('login') ?? ''));
        if ($login === '') {
            $this->newLine();
            $this->line('Aktivni Laravel korisnici:');
            $rows = DB::table('users')
                ->select(['id', 'username', 'email', 'status', 'role_id'])
                ->orderBy('id')
                ->limit(50)
                ->get()
                ->map(static fn ($row): array => [
                    (string) $row->id,
                    (string) $row->username,
                    (string) $row->email,
                    (string) $row->status,
                    (string) $row->role_id,
                ])->all();

            $this->table(['ID', 'Korisničko ime', 'E-mail', 'Status', 'Role ID'], $rows);

            if ($this->option('render-dashboard')) {
                $renderActor = User::query()
                    ->where('status', 'active')
                    ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
                    ->with(['role', 'group'])
                    ->orderBy('id')
                    ->first()
                    ?? User::query()
                        ->where('status', 'active')
                        ->whereHas('role')
                        ->with(['role', 'group'])
                        ->orderBy('id')
                        ->first();

                if (!$renderActor instanceof User) {
                    $this->failLine('Nema aktivnog korisnika za render početnog dashboarda.');
                    $failed = true;
                } elseif (!$this->renderDashboard($renderActor)) {
                    $failed = true;
                }
            }

            return $failed ? self::FAILURE : self::SUCCESS;
        }

        $this->newLine();
        $target = null;
        try {
            $target = $users->find($login);
        } catch (Throwable $exception) {
            $this->failLine('Laravel nalog ne može da se učita: '.$exception::class.': '.$exception->getMessage());
            $failed = true;
        }

        if ($target === null) {
            $this->failLine('Nalog nije pronađen u Laravel bazi.');
            $failed = true;
        } else {
            $roleSlug = 'nije povezana';
            try {
                $resolvedRole = $target->relationLoaded('role')
                    ? $target->role?->slug
                    : $target->role()->value('slug');
                if (is_string($resolvedRole) && trim($resolvedRole) !== '') {
                    $roleSlug = $resolvedRole;
                }
            } catch (Throwable $exception) {
                $roleSlug = 'nije dostupna';
                $this->failLine('Uloga naloga ne može da se učita: '.$exception::class.': '.$exception->getMessage());
                $failed = true;
            }

            $this->pass(sprintf(
                'Laravel nalog #%d: %s <%s>, status=%s, uloga=%s',
                $target->id,
                $target->username,
                $target->email,
                $target->status,
                $roleSlug,
            ));

            $hashInfo = password_get_info((string) $target->password_hash);
            $hashName = (string) ($hashInfo['algoName'] ?? 'unknown');
            if ($hashName === 'unknown') {
                $this->failLine('Password hash nije prepoznat kao podržan PHP hash.');
                $failed = true;
            } else {
                $this->pass('Password hash format: '.$hashName);
            }

            if ($target->status !== 'active') {
                $this->failLine('Nalog nije aktivan; trenutni status je '.$target->status.'.');
                $failed = true;
            }
        }

        try {
            $legacy = $recovery->findLegacy($login);
            if ($legacy === null) {
                $this->warn('Nalog nije pronađen u legacy bazi.');
            } else {
                $this->pass(sprintf(
                    'Legacy nalog #%d: %s <%s>, status=%s',
                    (int) ($legacy['id'] ?? 0),
                    (string) ($legacy['username'] ?? ''),
                    (string) ($legacy['email'] ?? ''),
                    (string) ($legacy['status'] ?? ''),
                ));
            }
        } catch (Throwable $exception) {
            $this->failLine('Legacy provera naloga nije uspela: '.$exception->getMessage());
            $failed = true;
        }

        if ($target !== null && $this->option('render-dashboard')) {
            $this->newLine();
            if (!$this->renderDashboard($target)) {
                $failed = true;
            }
        }

        $this->newLine();
        if ($target === null) {
            $this->line('Sledeći korak: php artisan app:reset-user-password '.escapeshellarg($login));
        } elseif ($target->status !== 'active') {
            $this->line('Aktiviraj nalog u Laravel bazi pre pokušaja prijave.');
        } else {
            $this->line('Sledeći korak: resetuj lozinku i zatim testiraj prijavu sa istim korisničkim imenom ili e-mailom.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function renderDashboard(User $user): bool
    {
        $application = app();
        $previousRequest = $application->bound('request') ? $application->make('request') : null;

        try {
            Auth::guard()->setUser($user);
            $request = Request::create('/', 'GET');
            $request->setUserResolver(static fn (): User => $user);
            $request->setLaravelSession($application->make('session')->driver());
            $application->instance('request', $request);
            View::share('errors', new ViewErrorBag());

            $view = app()->call([app(DashboardController::class), '__invoke'], [
                'request' => $request,
            ]);
            $html = $view->with('errors', new ViewErrorBag())->render();
            if (!str_contains($html, 'data-universal-dashboard-ready="1"') || !str_contains($html, 'Dobro došli')) {
                $this->failLine('Dashboard je renderovan, ali očekivani sadržaj nije pronađen.');
                return false;
            }

            $this->pass('Post-login dashboard i kompletan authenticated layout su uspešno renderovani.');
            return true;
        } catch (Throwable $exception) {
            $this->failLine('Post-login dashboard render nije uspeo: '.$exception::class.': '.$exception->getMessage());
            return false;
        } finally {
            if ($previousRequest instanceof Request) {
                $application->instance('request', $previousRequest);
            }
        }
    }

    private function pass(string $message): void
    {
        $this->line('<fg=green>PASS</> '.$message);
    }

    private function failLine(string $message): void
    {
        $this->line('<fg=red>FAIL</> '.$message);
    }
}
