<?php

declare(strict_types=1);
namespace Tests\Integration;

use App\Models\{Order, OrderPayment, ProductWarranty, User, WarrantyIssuance};
use App\Notifications\OperationalNotification;
use App\Services\{OrderPaymentService, WarrantyExpiryDeliveryService, WarrantyService, UserRetirementRelationPolicy};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Support\Facades\{DB, Notification, Schema};
use Tests\Concerns\Warranty797Fixtures;
use Tests\TestCase;

/** Explicit disposable database gate; not run on production or on shared generic test fixtures. */
final class Warranty797DatabaseSafetyTest extends TestCase
{
    use Warranty797Fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        self::assertSame('testing', app()->environment());
        self::assertSame('ald1n_warranty797_ci', DB::getDatabaseName());
        self::assertSame('127.0.0.1', config('database.connections.mysql.host'));
        self::assertTrue(function_exists('pcntl_fork'));
        $this->artisan('migrate:fresh',['--force'=>true])->assertExitCode(0);
        $this->seed(CoreAccessSeeder::class);
        config(['services.operational_notifications.mail_enabled'=>false, 'mobile.push.enabled'=>false]);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_22_210000_create_warranty_issuance_and_expiry_delivery_ledgers.php');
    }

    public function test_new_foreign_keys_are_explicitly_classified_and_no_history_is_rewritten(): void
    {
        [$order,$seller,$admin]=$this->order797('schema');
        $legacy=$this->warranty797($order); $snapshot=$legacy->fresh()->toArray();
        $report=app(UserRetirementRelationPolicy::class)->verifySchema();
        self::assertSame(123,$report['foreign_keys']);
        self::assertSame(['immutable_history','SET NULL'],UserRetirementRelationPolicy::USER_FKS['warranty_issuances.voided_by']);
        self::assertSame(['immutable_history','CASCADE'],UserRetirementRelationPolicy::USER_FKS['warranty_expiry_deliveries.user_id']);
        DB::statement('SET SESSION lock_wait_timeout = 13');
        $this->migration()->up();
        self::assertSame(13,(int)DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS value')->value);
        self::assertSame($snapshot,$legacy->fresh()->toArray());
    }

    public function test_blocked_parent_ddl_is_bounded_and_retry_has_complete_constraints(): void
    {
        [$order,,$admin]=$this->order797('ddl-lock');
        $before=DB::table('orders')->where('id',$order->id)->first();
        $this->migration()->down();
        $dir=sys_get_temp_dir().'/warranty797-lock-'.bin2hex(random_bytes(6)); mkdir($dir,0700);
        DB::disconnect();
        $pid=pcntl_fork(); self::assertNotSame(-1,$pid);
        if ($pid===0) {
            try {
                DB::purge(); DB::statement('LOCK TABLES orders WRITE'); file_put_contents($dir.'/ready','ready');
                $until=microtime(true)+20;
                while (!is_file($dir.'/release') && microtime(true)<$until) usleep(20000);
                DB::statement('UNLOCK TABLES'); DB::disconnect(); exit(0);
            } catch (\Throwable $e) { file_put_contents($dir.'/error',$e::class.': '.$e->getMessage()); exit(1); }
        }
        DB::purge(); $until=microtime(true)+8;
        while (!is_file($dir.'/ready') && !is_file($dir.'/error') && microtime(true)<$until) usleep(20000);
        try {
            self::assertFileExists($dir.'/ready',is_file($dir.'/error')?file_get_contents($dir.'/error'):'Lock child did not start');
            DB::statement('SET SESSION lock_wait_timeout = 13'); $started=microtime(true); $failure=null;
            try { $this->migration()->up(); } catch (\Throwable $e) { $failure=$e; }
            $elapsed=microtime(true)-$started;
            self::assertNotNull($failure,'Expected a metadata-lock timeout');
            self::assertStringContainsString('lock wait timeout',strtolower($failure->getMessage()));
            self::assertLessThan(12,$elapsed,'DDL must fail within its bounded wait');
            self::assertSame(13,(int)DB::selectOne('SELECT @@SESSION.lock_wait_timeout AS value')->value);
        } finally { file_put_contents($dir.'/release','release'); pcntl_waitpid($pid,$status); }
        self::assertSame(0,pcntl_wexitstatus($status),is_file($dir.'/error')?file_get_contents($dir.'/error'):'');
        $this->migration()->up();
        $fks=DB::table('information_schema.KEY_COLUMN_USAGE')->where('TABLE_SCHEMA',DB::getDatabaseName())
            ->where('TABLE_NAME','warranty_issuances')->whereNotNull('REFERENCED_TABLE_NAME')->pluck('COLUMN_NAME')->all();
        sort($fks); $expected=['order_id','order_item_id','confirmed_payment_id','activated_warranty_id','voided_by']; sort($expected);
        self::assertSame($expected,$fks,'A partial DDL retry must not silently accept a table missing foreign keys');
        self::assertEquals($before,DB::table('orders')->where('id',$order->id)->first());
        foreach (glob($dir.'/*')?:[] as $f) unlink($f); rmdir($dir);
    }

    public function test_concurrent_first_payment_confirmations_produce_one_certificate(): void
    {
        [$order,$seller,$admin]=$this->order797('parallel'); $payments=[];
        foreach ([1,2] as $n) $payments[]=OrderPayment::query()->create(['order_id'=>$order->id,'payment_number'=>'UPL797-parallel-'.$n,
            'entry_type'=>'payment','status'=>'submitted','amount_rsd'=>1,'payment_method'=>'bank_transfer','paid_at'=>now(),'submitted_by'=>$seller->id])->id;
        $this->parallel(static function (int $worker) use ($payments,$admin): void {
            app(OrderPaymentService::class)->verify(OrderPayment::query()->findOrFail($payments[$worker]),User::query()->findOrFail($admin->id));
        });
        self::assertSame(2,OrderPayment::query()->where('order_id',$order->id)->where('status','verified')->count());
        self::assertSame(1,WarrantyIssuance::query()->where('order_id',$order->id)->count());
        self::assertSame(0,ProductWarranty::query()->where('order_id',$order->id)->count());
        self::assertSame(2.0,(float)$order->fresh()->paid_total_rsd);
        try { $this->migration()->down(); self::fail('A populated issuance ledger must not be dropped'); }
        catch (\RuntimeException $e) { self::assertStringContainsString('populated warranty ledger',$e->getMessage()); }
        self::assertSame(1,WarrantyIssuance::query()->where('order_id',$order->id)->count());
    }

    public function test_concurrent_expiry_attempts_claim_and_send_once_per_recipient(): void
    {
        [$order,$seller]=$this->order797('parallel-expiry'); $w=$this->warranty797($order); $this->consent797($seller,true);
        $this->parallel(static function (int $worker) use ($seller,$w): void {
            app(WarrantyExpiryDeliveryService::class)->send(User::query()->findOrFail($seller->id),
                ['event'=>'warranty.expiring','warranty_id'=>$w->id,'title'=>'Synthetic expiry','message'=>'Test only']);
        });
        self::assertSame(1,DB::table('warranty_expiry_deliveries')->where('user_id',$seller->id)->count());
        self::assertSame(1,DB::table('notifications')->where('notifiable_id',$seller->id)->where('data->event','warranty.expiring')->count());
    }

    public function test_rolled_back_activation_does_not_emit_a_certificate_notification(): void
    {
        [$order,,$admin]=$this->order797('notify-rollback'); $order->update(['status'=>'shipped','completed_at'=>now()]);
        Notification::fake();
        DB::beginTransaction();
        try { app(WarrantyService::class)->ensureForOrder($order,$admin); }
        finally { DB::rollBack(); }
        self::assertSame(0,ProductWarranty::query()->count());
        Notification::assertNothingSent();
    }

    private function parallel(\Closure $operation): void
    {
        $dir=sys_get_temp_dir().'/warranty797-parallel-'.bin2hex(random_bytes(6)); mkdir($dir,0700);
        DB::disconnect(); $pids=[];
        foreach ([0,1] as $worker) {
            $pid=pcntl_fork(); self::assertNotSame(-1,$pid);
            if ($pid===0) {
                try {
                    DB::purge(); file_put_contents($dir.'/ready'.$worker,'ready'); $until=microtime(true)+10;
                    while (!is_file($dir.'/go') && microtime(true)<$until) usleep(10000);
                    if (!is_file($dir.'/go')) throw new \RuntimeException('Parallel barrier timeout');
                    $operation($worker); DB::disconnect(); exit(0);
                } catch (\Throwable $e) { file_put_contents($dir.'/error'.$worker,$e::class.': '.$e->getMessage()); exit(1); }
            }
            $pids[]=$pid;
        }
        $until=microtime(true)+10;
        while ((!is_file($dir.'/ready0') || !is_file($dir.'/ready1')) && microtime(true)<$until) usleep(10000);
        file_put_contents($dir.'/go','go'); $codes=[];
        foreach ($pids as $pid) { pcntl_waitpid($pid,$status); $codes[]=pcntl_wexitstatus($status); }
        DB::purge(); $errors=[];
        foreach (glob($dir.'/error*')?:[] as $p) $errors[]=file_get_contents($p);
        foreach (glob($dir.'/*')?:[] as $p) unlink($p); rmdir($dir);
        self::assertSame([0,0],$codes,implode('\n',$errors));
    }
}
