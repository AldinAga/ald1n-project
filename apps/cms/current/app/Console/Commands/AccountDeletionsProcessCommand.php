<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\UserDeletionService;
use Illuminate\Console\Command;

/**
 * Dnevna obrada zahteva za brisanje naloga kojima je istekao rok mirovanja.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class AccountDeletionsProcessCommand extends Command
{
    protected $signature = 'app:account-deletions-process';

    protected $description = 'Završava brisanje naloga kojima je istekao rok mirovanja (14 dana)';

    public function handle(UserDeletionService $deletions): int
    {
        $result = $deletions->processDue();
        $this->info(sprintf('Obrisano: %d, čeka obaveze: %d, greške: %d', $result['completed'], $result['waiting'], $result['failed']));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
