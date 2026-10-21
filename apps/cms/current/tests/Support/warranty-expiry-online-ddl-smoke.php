<?php

declare(strict_types=1);

/**
 * Destructive fixture confined to the disposable GitHub Actions MariaDB service.
 * This is a tracked CI test helper, NOT an operator/production script.
 */
if (getenv('CI') !== 'true' || getenv('APP_ENV') !== 'testing' || getenv('DB_DATABASE') !== 'ald1n_warranty_ci'
    || getenv('DB_HOST') !== '127.0.0.1') {
    fwrite(STDERR, "HOLD: CI-only MariaDB fixture guard failed.\n");
    exit(2);
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Role;
use App\Models\User;
use App\Services\UserNotificationPreferenceService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

function gate(bool $pass, string $label): void
{
    if (!$pass) {
        throw new RuntimeException('FAIL: '.$label);
    }
    echo 'PASS: '.$label."\n";
}

$version = (string) (DB::selectOne('SELECT VERSION() AS version')?->version ?? '');
gate(str_starts_with($version, '10.11.19-MariaDB'), 'disposable MariaDB 10.11.19 identity');
gate(DB::connection()->getDatabaseName() === 'ald1n_warranty_ci', 'effective disposable DB identity');
gate(Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts'), 'fresh schema includes new preference');

Artisan::call('db:seed', ['--class' => CoreAccessSeeder::class, '--force' => true]);
$roleId = (int) Role::query()->where('slug', 'user')->valueOrFail('id');
$users = [];
foreach ([true, false] as $i => $generalWarrantyEnabled) {
    $user = User::query()->create([
        'role_id' => $roleId,
        'user_group_id' => 1,
        'username' => 'ddl-optin-fixture-'.$i,
        'email' => 'ddl-optin-fixture-'.$i.'@example.test',
        'first_name' => 'Test',
        'password_hash' => Hash::make('IsolatedOnly123!'),
        'status' => 'active',
    ]);
    $pref = app(UserNotificationPreferenceService::class)->for($user);
    $pref->update(['warranty_updates' => $generalWarrantyEnabled]);
    $users[] = $user;
}
$before = DB::table('notification_preferences')->orderBy('id')->get(['id', 'user_id', 'warranty_updates', 'in_app_enabled', 'email_enabled'])->toArray();
gate(count($before) >= 2, 'existing notification preference rows created');

// Recreate the pre-migration state on a disposable CI DB using the actual table structure.
DB::statement('ALTER TABLE `notification_preferences` DROP COLUMN `warranty_expiry_alerts`, ALGORITHM=INSTANT, LOCK=NONE');
gate(!Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts'), 'pre-migration schema simulated');
$migration = require dirname(__DIR__, 2).'/database/migrations/2026_10_21_080000_add_warranty_expiry_opt_in.php';
$timeoutBefore = (string) (DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS seconds')?->seconds ?? '');

// A second connection holds an MDL read lock. The migration must fail within 5 seconds.
$holder = new PDO('mysql:host=127.0.0.1;port=3306;dbname=ald1n_warranty_ci', 'root', 'isolated-ci-only', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$holder->beginTransaction();
$holder->query('SELECT COUNT(*) FROM `notification_preferences`')->fetchColumn();
$blocked = false;
$started = microtime(true);
try {
    $migration->up();
} catch (\Illuminate\Database\QueryException $exception) {
    $blocked = (int) ($exception->errorInfo[1] ?? 0) === 1205;
    if (!$blocked) {
        throw $exception;
    }
} finally {
    $holder->rollBack();
}
gate($blocked && microtime(true) - $started < 12, 'DDL fails closed on real metadata lock within bounded wait');
gate(!Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts'), 'failed DDL did not modify schema');
gate((string) (DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS seconds')?->seconds ?? '') === $timeoutBefore, 'session lock timeout restored after failure');

$migration->up();
gate(Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts'), 'INSTANT add-column succeeds after lock release');
$after = DB::table('notification_preferences')->orderBy('id')->get(['id', 'user_id', 'warranty_updates', 'in_app_enabled', 'email_enabled', 'warranty_expiry_alerts'])->toArray();
gate(count($before) === count($after), 'existing preference count preserved');
foreach ($before as $index => $row) {
    foreach (['id', 'user_id', 'warranty_updates', 'in_app_enabled', 'email_enabled'] as $key) {
        gate((string) $row->{$key} === (string) $after[$index]->{$key}, 'legacy field preserved '.$key.' row '.$index);
    }
    gate((int) $after[$index]->warranty_expiry_alerts === 0, 'existing user defaults to opt-out row '.$index);
}
gate((string) (DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS seconds')?->seconds ?? '') === $timeoutBefore, 'session lock timeout restored after success');
$migration->up();
gate(Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts'), 'migration up is idempotent');

// Automated rollback must never silently delete an existing explicit opt-in.
DB::table('notification_preferences')->where('user_id', $users[0]->id)->update(['warranty_expiry_alerts' => true]);
$refused = false;
try {
    $migration->down();
} catch (RuntimeException) {
    $refused = true;
}
gate($refused && (int) DB::table('notification_preferences')->where('user_id', $users[0]->id)->value('warranty_expiry_alerts') === 1, 'rollback rejects destruction of explicit opt-in');
DB::table('notification_preferences')->where('user_id', $users[0]->id)->update(['warranty_expiry_alerts' => false]);
$migration->down();
gate(!Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts'), 'INSTANT down permitted only when no user opted in');
$migration->up();
gate(Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts'), 'migration reapply is safe');

echo "WARRANTY_EXPIRY_ONLINE_DDL_MARIADB=PASS\n";
