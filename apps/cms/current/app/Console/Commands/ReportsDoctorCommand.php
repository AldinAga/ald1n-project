<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\Admin\ReportController;
use App\Models\User;
use App\Services\OrderReportService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ReportsDoctorCommand extends Command
{
    protected $signature = 'app:reports-doctor
        {--repair : Pokreni migracije i CoreAccessSeeder pre dijagnostike}
        {--render : Renderuj isti Reports kontroler, Blade i authenticated layout koji browser koristi}';

    protected $description = 'Proveri šemu, SQL upite i kompletan server-side render admin Reports stranice';

    public function handle(OrderReportService $reports): int
    {
        if ($this->option('repair')) {
            $this->line('Pokrećem bezbedan reports repair...');

            try {
                $migrationExit = Artisan::call('migrate', ['--force' => true]);
                $this->output->write(Artisan::output());
                if ($migrationExit !== self::SUCCESS) {
                    $this->error('Migracije nisu završene uspešno.');
                    return self::FAILURE;
                }

                Artisan::call('db:seed', [
                    '--class' => CoreAccessSeeder::class,
                    '--force' => true,
                ]);
                $this->output->write(Artisan::output());
            } catch (Throwable $exception) {
                $this->error('Repair nije uspeo: '.$exception::class.': '.$exception->getMessage());
                return self::FAILURE;
            }
        }

        try {
            $issues = $reports->readinessIssues();
        } catch (Throwable $exception) {
            $this->error('Šema izveštaja ne može da se proveri: '.$exception::class.': '.$exception->getMessage());
            return self::FAILURE;
        }

        if ($issues !== []) {
            foreach ($issues as $issue) {
                $this->line('<fg=red>FAIL</> '.$issue);
            }
            $this->line('Pokreni: php artisan app:reports-doctor --repair');
            return self::FAILURE;
        }

        $this->line('<fg=green>PASS</> Sve potrebne reports tabele i kolone postoje.');

        try {
            $user = User::query()
                ->where('status', 'active')
                ->whereHas('role', static fn ($query) => $query->whereIn('slug', ['superadmin', 'admin']))
                ->with('role')
                ->orderBy('id')
                ->first();

            if ($user === null) {
                $this->error('Nema aktivnog SuperAdministratora ili Administratora za stvarni reports upit.');
                return self::FAILURE;
            }

            $summary = $reports->summary($user, []);
            $page = $reports->paginate($user, [], 5);

            $this->line('<fg=green>PASS</> Reports SQL upiti su uspešni.');
            $this->line(sprintf(
                'Korisnik #%d, porudžbine=%d, vrednost=%.2f RSD, komada=%d, provizije=%.2f EUR, prvi page=%d.',
                $user->id,
                $summary['orders_count'],
                $summary['total_rsd'],
                $summary['units_count'],
                $summary['commission_eur'],
                $page->count(),
            ));
        } catch (Throwable $exception) {
            $this->error('Reports SQL upit nije uspeo: '.$exception::class.': '.$exception->getMessage());
            return self::FAILURE;
        }

        if ($this->option('render')) {
            if (!$this->renderReports($user, $reports)) {
                return self::FAILURE;
            }
        } else {
            $this->line('INFO Za proveru iste render faze koju koristi browser pokreni: php artisan app:reports-doctor --render');
        }

        return self::SUCCESS;
    }

    private function renderReports(User $user, OrderReportService $reports): bool
    {
        $application = app();
        $previousRequest = $application->bound('request') ? $application->make('request') : null;

        try {
            Auth::guard()->setUser($user);
            $request = Request::create('/admin/reports', 'GET');
            $request->setUserResolver(static fn (): User => $user);
            $request->setLaravelSession($application->make('session')->driver());
            $application->instance('request', $request);
            View::share('errors', new ViewErrorBag());

            $response = app(ReportController::class)->index($request, $reports);
            if (!$response instanceof Response) {
                $this->line('<fg=red>FAIL</> Reports kontroler nije vratio HTTP odgovor.');
                return false;
            }

            $html = (string) $response->getContent();
            if ($response->getStatusCode() !== 200) {
                $this->line('<fg=red>FAIL</> Reports render je vratio HTTP '.$response->getStatusCode().'.');
                return false;
            }

            if (str_contains($html, 'reports-page-ready')) {
                $this->line('<fg=green>PASS</> Reports kontroler, Blade i kompletan authenticated layout su uspešno renderovani.');
                return true;
            }

            if (str_contains($html, 'Reports su dostupni u recovery režimu') || str_contains($html, 'Reports Blade/layout render nije uspeo')) {
                $this->line('<fg=red>FAIL</> Reports je pao u recovery HTML. Proveri storage/logs/laravel.log za poruku "Reports Blade/layout render nije uspeo".');
                return false;
            }

            $this->line('<fg=red>FAIL</> Reports odgovor nema očekivani marker sadržaja.');
            return false;
        } catch (Throwable $exception) {
            $this->line('<fg=red>FAIL</> Reports render nije uspeo: '.$exception::class.': '.$exception->getMessage());
            return false;
        } finally {
            if ($previousRequest instanceof Request) {
                $application->instance('request', $previousRequest);
            }
        }
    }
}
