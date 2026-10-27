<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WarrantyActivationRecoveryService;
use Illuminate\Console\Command;
use Throwable;

final class WarrantyActivationRecoveryCommand extends Command
{
    protected $signature = 'app:warranty-activation-recover
        {--limit=25 : Najviše toliko porudžbina (1-50)}
        {--execute : Zahteva posebno odobren WARRANTY_ACTIVATION_RECOVERY_ENABLED}';

    protected $description = 'Bezbedna provera ili kontrolisani oporavak isporučenih, prethodno plaćenih garancija (podrazumevano samo čitanje).';

    public function handle(WarrantyActivationRecoveryService $recovery): int
    {
        try {
            $result = $recovery->run((int) $this->option('limit'), (bool) $this->option('execute'));
        } catch (Throwable $exception) {
            $this->error('HOLD_WARRANTY_RECOVERY: '.$exception::class);
            return self::FAILURE;
        }

        $this->line(sprintf(
            '%s mode=%s candidates=%d recovered=%d failed=%d deferred=%d manual_review=%d manual_review_total=%d skipped=%d',
            $result['failed'] || $result['manual_review_total'] ? 'HOLD' : 'PASS',
            $result['mode'],
            $result['candidates'],
            $result['recovered'],
            $result['failed'],
            $result['deferred'],
            $result['manual_review'],
            $result['manual_review_total'],
            $result['skipped'],
        ));

        return $result['failed'] || $result['manual_review_total'] ? self::FAILURE : self::SUCCESS;
    }
}
