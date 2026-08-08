<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class CacheDoctorCommand extends Command
{
    protected $signature = 'app:cache-doctor {--store= : Proveri određeni konfigurisani cache store}';
    protected $description = 'Proveri file/database/array Laravel cache bez prikazivanja kredencijala';

    public function handle(): int
    {
        $store = trim((string) ($this->option('store') ?: config('cache.default')));
        $configuredStores = array_keys((array) config('cache.stores', []));

        $this->line('Cache store: '.$store);
        $this->line('Session driver: '.(string) config('session.driver'));
        $this->line('Redis: ISKLJUČEN u v2.0.0-beta2');

        if (!in_array($store, $configuredStores, true)) {
            $this->error('Cache store nije konfigurisan. Dozvoljeno: '.implode(', ', $configuredStores));
            return self::FAILURE;
        }

        $key = 'ald1n-cache-doctor-'.bin2hex(random_bytes(8));
        $value = bin2hex(random_bytes(16));

        try {
            $cache = Cache::store($store);
            $cache->put($key, $value, 60);
            $read = $cache->get($key);
            $cache->forget($key);
        } catch (Throwable $exception) {
            $this->error('Cache test nije uspeo: '.$exception->getMessage());
            return self::FAILURE;
        }

        if (!is_string($read) || !hash_equals($value, $read)) {
            $this->error('Cache je dostupan, ali upisana i pročitana vrednost nisu iste.');
            return self::FAILURE;
        }

        $this->info('PASS Cache upis, čitanje i brisanje rade.');
        return self::SUCCESS;
    }
}
