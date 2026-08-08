<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class TestDatabaseDoctorCommand extends Command
{
    protected $signature = 'app:test-database-doctor {--migrate-fresh : OBRIŠI i ponovo kreiraj isključivo potvrđenu test bazu}';
    protected $description = 'Bezbedno proveri izolovanu MySQL test bazu';

    public function handle(): int
    {
        try {
            $database = trim((string) config('database.connections.mysql.database'));
            $confirmed = trim((string) env('TEST_DB_CONFIRM_DATABASE', ''));
            $allowed = filter_var(env('ALLOW_TEST_DATABASE_RESET', false), FILTER_VALIDATE_BOOL);
            if (!app()->environment('testing')) throw new RuntimeException('Komandu pokreni sa --env=testing.');
            if ($database === '' || !str_ends_with(mb_strtolower($database), '_test')) throw new RuntimeException('Naziv baze mora da se završava sa _test.');
            if ($confirmed === '' || !hash_equals($database, $confirmed)) throw new RuntimeException('TEST_DB_CONFIRM_DATABASE mora biti identičan aktivnoj test bazi.');
            if (!$allowed) throw new RuntimeException('ALLOW_TEST_DATABASE_RESET=true nije postavljen.');
            DB::connection()->getPdo();
            $this->info('PASS Izolovana test baza: '.$database);
            if ($this->option('migrate-fresh')) {
                $code = Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
                $this->line(trim(Artisan::output()));
                if ($code !== self::SUCCESS) return self::FAILURE;
                $this->info('PASS Test baza je migrirana od nule.');
            }
            $this->line('Pokreni: php artisan --env=testing test -c phpunit.mysql.xml --testsuite=Feature');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('FAIL '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
