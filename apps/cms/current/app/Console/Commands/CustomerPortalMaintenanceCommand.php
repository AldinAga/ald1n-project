<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CustomerActivationService;
use App\Services\PortalSessionService;
use Illuminate\Console\Command;
use Throwable;

final class CustomerPortalMaintenanceCommand extends Command
{
    protected $signature = 'app:customer-portal-maintenance';
    protected $description = 'Uklanja stare aktivacione tokene i zatvara zastarele evidencije prijava Customer Portala.';

    public function handle(CustomerActivationService $activations, PortalSessionService $sessions): int
    {
        try {
            $tokens = $activations->purgeExpired();
            $loginSessions = $sessions->expireStale();
        } catch (Throwable $exception) {
            $this->error('Customer Portal održavanje nije uspelo: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Customer Portal održavanje je završeno.');
        $this->line('Obrisani tokeni: '.$tokens);
        $this->line('Zatvorene zastarele prijave: '.$loginSessions);

        return self::SUCCESS;
    }
}
