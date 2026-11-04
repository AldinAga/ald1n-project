<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MobileDevice;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// A disposable second PHP process exercises the actual HTTP kernel, not a copied controller.
if (PHP_SAPI === 'cli' && ($argv[1] ?? null) === 'b1124-worker') {
    require dirname(__DIR__, 2).'/vendor/autoload.php';
    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();
    $job = json_decode(file_get_contents($argv[2]), true, flags: JSON_THROW_ON_ERROR);
    if (app()->environment() !== 'testing' || DB::connection()->getDatabaseName() !== 'ald1n_retirement_ci') exit(90);
    Http::preventStrayRequests();
    $connection = DB::connection();
    $id = (int) $connection->getPdo()->query('SELECT CONNECTION_ID()')->fetchColumn();
    $seen = false;
    $connection->beforeExecuting(function (string $query) use ($job, $id, &$seen): void {
        if (!str_starts_with(strtolower(ltrim($query)), 'select') || !str_contains($query, 'mobile_devices')) return;
        if ($seen) return;
        $seen = true;
        file_put_contents($job['dir'].'/query.json', json_encode(['id' => $id, 'locked' => str_contains(strtolower($query), 'for update')]));
    });
    if ($job['hold_after_select'] ?? false) {
        $held = false;
        DB::listen(function ($event) use ($job, $id, &$held): void {
            if ($held || !str_starts_with(strtolower(ltrim($event->sql)), 'select') || !str_contains($event->sql, 'mobile_devices')) return;
            $held = true;
            file_put_contents($job['dir'].'/selected.json', json_encode(['id' => $id, 'locked' => str_contains(strtolower($event->sql), 'for update')]));
            $deadline = microtime(true) + 12;
            while (!is_file($job['dir'].'/release') && microtime(true) < $deadline) usleep(10000);
            if (!is_file($job['dir'].'/release')) throw new \RuntimeException('B1124 worker barrier timeout');
        });
    }
    $request = Request::create($job['uri'], $job['method'], $job['data'], server: [
        'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$job['token'],
    ]);
    $response = $kernel->handle($request);
    $body = json_decode($response->getContent(), true);
    file_put_contents($job['dir'].'/result.json', json_encode([
        'status' => $response->getStatusCode(), 'id' => $body['data']['id'] ?? null,
        'enabled' => $body['data']['notifications_enabled'] ?? null,
        'registered' => $body['data']['push_registered'] ?? null, 'connection' => $id,
    ]));
    $kernel->terminate($request, $response);
    exit(0);
}

final class PassiveDeviceConsentB1124Test extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        self::assertSame('testing', app()->environment());
        self::assertSame('ald1n_retirement_ci', DB::connection()->getDatabaseName());
        $this->seed(CoreAccessSeeder::class);
        Http::preventStrayRequests();
    }

    public function test_passive_post_cannot_restore_a_disabled_token(): void
    {
        $user = $this->user('post');
        $device = $this->device($user, false);
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/devices', $this->payload($device))->assertOk()->assertJsonPath('data.notifications_enabled', false)->assertJsonPath('data.push_registered', false);
        $this->assertDisabled($device);
    }

    public function test_passive_patch_cannot_restore_a_disabled_token(): void
    {
        $user = $this->user('patch');
        $device = $this->device($user, false);
        Sanctum::actingAs($user);
        $this->patchJson('/api/v1/devices/'.$device->id, $this->token())->assertOk()->assertJsonPath('data.notifications_enabled', false)->assertJsonPath('data.push_registered', false);
        $this->assertDisabled($device);
    }

    public function test_disabled_passive_post_cannot_revoke_another_owners_token(): void
    {
        $this->assertForeignTokenUntouched('POST');
    }

    public function test_disabled_passive_patch_cannot_revoke_another_owners_token(): void
    {
        $this->assertForeignTokenUntouched('PATCH');
    }

    public function test_store_rechecks_a_disable_committed_at_transaction_entry(): void
    {
        $this->assertEntryDisable('POST');
    }

    public function test_update_rechecks_a_disable_committed_at_transaction_entry(): void
    {
        $this->assertEntryDisable('PATCH');
    }

    public function test_explicit_true_post_and_patch_remain_intentional_enable_paths(): void
    {
        $user = $this->user('enable');
        $device = $this->device($user, false);
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/devices', $this->payload($device) + ['notifications_enabled' => true])->assertOk()->assertJsonPath('data.notifications_enabled', true)->assertJsonPath('data.push_registered', true);
        $this->patchJson('/api/v1/devices/'.$device->id, ['notifications_enabled' => false, 'push_provider' => null, 'push_token' => null])->assertOk();
        $this->patchJson('/api/v1/devices/'.$device->id, $this->token('enabled-again') + ['notifications_enabled' => true])->assertOk()->assertJsonPath('data.notifications_enabled', true)->assertJsonPath('data.push_registered', true);
    }

    public function test_new_and_revoked_registration_defaults_and_explicit_false_are_preserved(): void
    {
        $user = $this->user('defaults');
        Sanctum::actingAs($user);
        $installation = (string) Str::uuid();
        $this->postJson('/api/v1/devices', ['installation_id' => $installation, 'platform' => 'android'])->assertCreated()->assertJsonPath('data.notifications_enabled', true);
        $device = $user->mobileDevices()->sole();
        $this->deleteJson('/api/v1/devices/'.$device->id)->assertNoContent();
        $this->postJson('/api/v1/devices', ['installation_id' => $installation, 'platform' => 'android'])->assertOk()->assertJsonPath('data.notifications_enabled', true)->assertJsonPath('data.active', true);
        $this->postJson('/api/v1/devices', ['installation_id' => (string) Str::uuid(), 'platform' => 'ios', 'notifications_enabled' => false])->assertCreated()->assertJsonPath('data.notifications_enabled', false);
    }

    public function test_fresh_destroy_revokes_the_rotated_owned_auth_token(): void
    {
        $user = $this->user('destroy');
        $old = $user->createToken('b1124-old');
        $new = $user->createToken('b1124-new');
        $device = $this->device($user, true);
        $device->forceFill(['personal_access_token_id' => $old->accessToken->id])->save();
        $this->atTransactionEntry(function (\PDO $pdo) use ($device, $new, $old): void {
            $pdo->prepare('UPDATE mobile_devices SET personal_access_token_id=? WHERE id=?')->execute([$new->accessToken->id, $device->id]);
            $pdo->prepare('DELETE FROM personal_access_tokens WHERE id=?')->execute([$old->accessToken->id]);
        });
        $this->withToken($old->plainTextToken)->deleteJson('/api/v1/devices/'.$device->id)->assertNoContent();
        self::assertNull($device->fresh()->personal_access_token_id);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $new->accessToken->id]);
    }

    public function test_fresh_update_ownership_is_rechecked_and_private_token_is_never_exposed(): void
    {
        $user = $this->user('owner');
        $other = $this->user('other');
        $device = $this->device($user, true);
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/devices')->assertOk()->assertJsonMissingPath('data.0.push_token')->assertJsonMissingPath('data.0.push_token_hash');
        $this->atTransactionEntry(function (\PDO $pdo) use ($device, $other): void {
            $pdo->prepare('UPDATE mobile_devices SET user_id=? WHERE id=?')->execute([$other->id, $device->id]);
        });
        $this->patchJson('/api/v1/devices/'.$device->id, ['device_name' => 'Must not change'])->assertNotFound();
        self::assertNotSame('Must not change', $device->fresh()->device_name);
        self::assertSame($other->id, $device->fresh()->user_id);
    }

    public function test_claim_failure_rolls_back_foreign_revocation_and_own_device(): void
    {
        $user = $this->user('rollback');
        $other = $this->device($this->user('foreign-rollback'), true, 'rollback-foreign');
        $own = $this->device($user, true, 'rollback-own');
        $before = [$other->getRawOriginal(), $own->getRawOriginal()];
        Event::listen('eloquent.saving: '.MobileDevice::class, function (MobileDevice $model) use ($own): void {
            if ((int) $model->id === (int) $own->id) throw new \RuntimeException('Synthetic B1124 rollback');
        });
        Sanctum::actingAs($user);
        $this->patchJson('/api/v1/devices/'.$own->id, ['push_provider' => 'expo', 'push_token' => $other->push_token, 'notifications_enabled' => true])->assertStatus(500);
        self::assertSame($before, [$other->fresh()->getRawOriginal(), $own->fresh()->getRawOriginal()]);
    }

    public function test_committed_disable_wins_before_a_waiting_passive_registration(): void
    {
        $user = $this->user('disable-first');
        $device = $this->device($user, true);
        $token = $user->createToken('b1124-worker');
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE mobile_devices SET notifications_enabled=0,push_provider=NULL,push_token=NULL,push_token_hash=NULL WHERE id=?')->execute([$device->id]);
        $worker = $this->worker($token->plainTextToken, 'POST', '/api/v1/devices', $this->payload($device));
        try {
            $query = $this->waitJson($worker['dir'].'/query.json');
            self::assertTrue($query['locked'], 'Passive registration must wait on its fresh owned row');
            self::assertNotSame((int) $pdo->query('SELECT CONNECTION_ID()')->fetchColumn(), $query['id']);
            $this->assertWaiting($pdo, $query['id']);
            $pdo->commit();
            $result = $this->finish($worker);
            self::assertSame(200, $result['status']);
            self::assertFalse($result['enabled']);
            self::assertFalse($result['registered']);
            $this->assertDisabled($device);
        } finally {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $this->stop($worker);
        }
    }

    public function test_explicit_disable_wins_after_passive_refresh_acquires_its_row(): void
    {
        $user = $this->user('refresh-first');
        $device = $this->device($user, true);
        $token = $user->createToken('b1124-refresh-worker');
        $refresh = $this->worker($token->plainTextToken, 'POST', '/api/v1/devices', $this->payload($device), true);
        $disable = null;
        try {
            $selected = $this->waitJson($refresh['dir'].'/selected.json');
            self::assertTrue($selected['locked'], 'Refresh must own its row before changing token state');
            $disable = $this->worker($token->plainTextToken, 'PATCH', '/api/v1/devices/'.$device->id, ['notifications_enabled' => false, 'push_provider' => null, 'push_token' => null]);
            $query = $this->waitJson($disable['dir'].'/query.json');
            // Route binding may read before the controller's locked reload; observe the actual blocking SQL too.
            $this->assertWaiting($this->pdo(), $query['id']);
            touch($refresh['dir'].'/release');
            self::assertSame(200, $this->finish($refresh)['status']);
            self::assertSame(200, $this->finish($disable)['status']);
            $this->assertDisabled($device);
        } finally {
            touch($refresh['dir'].'/release');
            $this->stop($refresh);
            if ($disable !== null) $this->stop($disable);
        }
    }

    public function test_same_installation_contention_and_cross_owner_swap_preserve_unique_claims(): void
    {
        $user = $this->user('parallel');
        $auth = $user->createToken('b1124-parallel');
        $installation = (string) Str::uuid();
        $data = ['installation_id' => $installation, 'platform' => 'android'];
        $a = $this->worker($auth->plainTextToken, 'POST', '/api/v1/devices', $data, true);
        $b = $this->worker($auth->plainTextToken, 'POST', '/api/v1/devices', $data, true);
        try {
            self::assertTrue($this->waitJson($a['dir'].'/selected.json')['locked']);
            self::assertTrue($this->waitJson($b['dir'].'/selected.json')['locked']);
            touch($a['dir'].'/release'); touch($b['dir'].'/release');
            $statuses = [$this->finish($a)['status'], $this->finish($b)['status']];
            sort($statuses);
            self::assertSame([200, 201], $statuses);
            self::assertSame(1, $user->mobileDevices()->where('installation_id', $installation)->count());
        } finally { touch($a['dir'].'/release'); touch($b['dir'].'/release'); $this->stop($a); $this->stop($b); }

        $otherUser = $this->user('swap');
        $left = $this->device($user, true, 'left');
        $right = $this->device($otherUser, true, 'right');
        $otherAuth = $otherUser->createToken('b1124-swap');
        $a = $this->worker($auth->plainTextToken, 'POST', '/api/v1/devices', $this->payload($left, $right->push_token) + ['notifications_enabled' => true], true);
        $b = $this->worker($otherAuth->plainTextToken, 'POST', '/api/v1/devices', $this->payload($right, $left->push_token) + ['notifications_enabled' => true], true);
        try {
            self::assertTrue($this->waitJson($a['dir'].'/selected.json')['locked']);
            self::assertTrue($this->waitJson($b['dir'].'/selected.json')['locked']);
            touch($a['dir'].'/release'); touch($b['dir'].'/release');
            self::assertSame(200, $this->finish($a)['status']);
            self::assertSame(200, $this->finish($b)['status']);
            self::assertNotSame($left->fresh()->push_token_hash, $right->fresh()->push_token_hash);
            self::assertSame(2, MobileDevice::query()->whereIn('id', [$left->id, $right->id])->whereNotNull('push_token_hash')->count());
        } finally { touch($a['dir'].'/release'); touch($b['dir'].'/release'); $this->stop($a); $this->stop($b); }
    }

    private function user(string $suffix): User
    {
        return User::query()->create(['role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'), 'username' => 'b1124-'.$suffix, 'email' => 'b1124-'.$suffix.'@synthetic.example.test', 'password_hash' => Hash::make('SyntheticB1124Only!'), 'first_name' => 'Synthetic', 'last_name' => 'B1124', 'status' => 'active']);
    }

    private function device(User $user, bool $enabled, string $suffix = 'device'): MobileDevice
    {
        $token = 'ExponentPushToken[b1124-'.$user->id.'-'.$suffix.']';
        return $user->mobileDevices()->create(['installation_id' => (string) Str::uuid(), 'platform' => 'android', 'device_name' => 'Synthetic B1124', 'push_provider' => $enabled ? 'expo' : null, 'push_token' => $enabled ? $token : null, 'push_token_hash' => $enabled ? hash('sha256', $token) : null, 'notifications_enabled' => $enabled, 'last_seen_at' => now()]);
    }

    private function token(string $suffix = 'refreshed'): array { return ['push_provider' => 'expo', 'push_token' => 'ExponentPushToken[b1124-'.$suffix.']']; }
    private function payload(MobileDevice $device, ?string $token = null): array { return ['installation_id' => $device->installation_id, 'platform' => 'android'] + ($token === null ? $this->token() : ['push_provider' => 'expo', 'push_token' => $token]); }
    private function assertDisabled(MobileDevice $device): void { $fresh = $device->fresh(); self::assertFalse($fresh->notifications_enabled); self::assertNull($fresh->push_provider); self::assertNull($fresh->push_token); self::assertNull($fresh->push_token_hash); Http::assertNothingSent(); }

    private function assertForeignTokenUntouched(string $method): void
    {
        $user = $this->user(strtolower($method).'-claim');
        $own = $this->device($user, false);
        $foreign = $this->device($this->user(strtolower($method).'-foreign'), true);
        $before = $foreign->getRawOriginal();
        Sanctum::actingAs($user);
        $data = $method === 'POST' ? $this->payload($own, $foreign->push_token) : ['push_provider' => 'expo', 'push_token' => $foreign->push_token];
        $this->json($method, $method === 'POST' ? '/api/v1/devices' : '/api/v1/devices/'.$own->id, $data)->assertOk();
        self::assertSame($before, $foreign->fresh()->getRawOriginal());
        $this->assertDisabled($own);
    }

    private function assertEntryDisable(string $method): void
    {
        $user = $this->user(strtolower($method).'-stale');
        $device = $this->device($user, true);
        Sanctum::actingAs($user);
        $this->atTransactionEntry(function (\PDO $pdo) use ($device): void { $pdo->prepare('UPDATE mobile_devices SET notifications_enabled=0,push_provider=NULL,push_token=NULL,push_token_hash=NULL WHERE id=?')->execute([$device->id]); });
        $this->json($method, $method === 'POST' ? '/api/v1/devices' : '/api/v1/devices/'.$device->id, $method === 'POST' ? $this->payload($device) : $this->token())->assertOk()->assertJsonPath('data.notifications_enabled', false);
        $this->assertDisabled($device);
    }

    private function atTransactionEntry(callable $mutate): void
    {
        $once = false;
        Event::listen(TransactionBeginning::class, function ($event) use (&$once, $mutate): void {
            if ($once || $event->connectionName !== config('database.default')) return;
            $once = true;
            $mutate($this->pdo());
        });
    }

    private function pdo(): \PDO
    {
        $c = config('database.connections.'.config('database.default'));
        return new \PDO('mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$c['database'], $c['username'], $c['password'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
    }

    private function worker(string $token, string $method, string $uri, array $data, bool $hold = false): array
    {
        $dir = sys_get_temp_dir().'/b1124-'.bin2hex(random_bytes(12));
        self::assertTrue(mkdir($dir, 0700));
        file_put_contents($dir.'/job.json', json_encode(['dir' => $dir, 'token' => $token, 'method' => $method, 'uri' => $uri, 'data' => $data, 'hold_after_select' => $hold], JSON_THROW_ON_ERROR));
        $process = proc_open([PHP_BINARY, __FILE__, 'b1124-worker', $dir.'/job.json'], [0 => ['file', '/dev/null', 'r'], 1 => ['file', $dir.'/stdout', 'w'], 2 => ['file', $dir.'/stderr', 'w']], $pipes, base_path());
        self::assertIsResource($process);
        return ['dir' => $dir, 'process' => $process];
    }

    private function waitJson(string $file): array
    {
        $deadline = microtime(true) + 12;
        do { if (is_file($file) && ($value = json_decode(file_get_contents($file), true)) !== null) return $value; usleep(10000); } while (microtime(true) < $deadline);
        self::fail('B1124 bounded worker marker missing: '.basename($file));
    }

    private function assertWaiting(\PDO $pdo, int $id): void
    {
        $deadline = microtime(true) + 8;
        $observations = 0;
        do {
            foreach ($pdo->query('SHOW FULL PROCESSLIST')->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                if ((int) $row['Id'] === $id && str_contains(strtolower((string) $row['Info']), 'mobile_devices') && str_contains(strtolower((string) $row['Info']), 'for update')) $observations++;
            }
            if ($observations >= 2) return;
            usleep(20000);
        } while (microtime(true) < $deadline);
        self::fail('B1124 independent request did not observably wait on its owned device row');
    }

    private function finish(array $worker): array { $result = $this->waitJson($worker['dir'].'/result.json'); self::assertSame(0, proc_close($worker['process'])); return $result; }
    private function stop(array $worker): void { if (is_resource($worker['process'])) { proc_terminate($worker['process']); proc_close($worker['process']); } }
}
