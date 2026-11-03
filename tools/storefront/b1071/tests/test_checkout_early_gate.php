<?php
declare(strict_types=1);
namespace App\Models {
    final class User {
        public int $id=7;
        public string $email='buyer@example.test';
        public string $status='active';
        public mixed $retired_at=null;
        public string $email_verified_at='2026-01-01';
        public string $portal_activated_at='2026-01-01';
        public function hasRole(string $name): bool { return $name==='customer'; }
    }
}
namespace Illuminate\Http {
    final class Request {
        public function __construct(private object $buyer, private object $state) {}
        public function user(): object { return $this->buyer; }
        public function session(): object { return $this->state; }
    }
}
namespace Illuminate\Validation {
    final class ValidationException extends \InvalidArgumentException {
        public static function withMessages(array $errors): self { return new self(json_encode($errors,JSON_THROW_ON_ERROR)); }
    }
}
namespace Illuminate\Support\Facades {
    final class DB {
        public static int $transactions=0;
        public static function transaction(callable $callback,int $attempts=1): never {
            self::$transactions++;
            throw new \RuntimeException('DB_BOUNDARY_SIMULATED');
        }
    }
}
namespace App\Services {
    final class PublicRetailCatalogService {}
    final class PublicCheckoutReadinessService {
        public function assertReady(): array { return [
            'approval_digest'=>str_repeat('a',64),
            'delivery_methods'=>['courier'],
            'methods'=>['cash_on_delivery'],
        ];}
    }
    final class SettingsService {}
    final class PublicOrderAccessService {}
}
namespace {
    // Local PHP CLI fixture has no Laravel helpers or mbstring extension.
    // Test inputs use ASCII synthetic identities only.
    if (!function_exists('mb_strtolower')) {
        function mb_strtolower(string $value): string { return strtolower($value); }
    }
    function config(string $key): string { return 'synthetic-test-app-key'; }
    function abort_unless(bool $condition, int $code): void {
        if (!$condition) throw new \RuntimeException('HTTP_'.(string)$code);
    }
    $root=dirname(__DIR__,4);
    require $root.'/apps/cms/current/app/Support/PublicRetailCartReplaySnapshot.php';
    require $root.'/apps/cms/current/app/Services/PublicCartService.php';
    require $root.'/apps/cms/current/app/Services/PublicRetailCheckoutService.php';
    final class State {
        public function __construct(private array $data) {}
        public function get(string $k,mixed $default=null): mixed {return $this->data[$k]??$default;}
        public function put(string $k,mixed $v): void {$this->data[$k]=$v;}
        public function forget(string $k): void {unset($this->data[$k]);}
    }
    $cart=new \App\Services\PublicCartService(new \App\Services\PublicRetailCatalogService());
    $service=new \App\Services\PublicRetailCheckoutService(
        new \App\Services\PublicRetailCatalogService(),$cart,
        new \App\Services\PublicCheckoutReadinessService(),
        new \App\Services\SettingsService(),
        new \App\Services\PublicOrderAccessService());
    $key=str_repeat('b',48);
    $input=[
        'checkout_key'=>$key, 'merchant_policy_fingerprint'=>str_repeat('a',64),
        'email'=>'buyer@example.test','delivery_method'=>'courier','payment_method'=>'cash_on_delivery',
        'shipping_full_name'=>'Synthetic buyer', 'shipping_address'=>'Synthetic address',
        'shipping_city'=>'Beograd', 'shipping_postal_code'=>'11000',
        'shipping_phone'=>'0600000000',
    ];
    $cases=[
        'string_quantity_rejected_before_db'=>[[3=>'2'],false],
        'zero_quantity_rejected_before_db'=>[[3=>0],false],
        'non_array_rejected_before_db'=>['not an array',false],
        'valid_exact_replay_reaches_db_boundary'=>[[3=>2],true],
    ];
    $passed=0;
    foreach($cases as $name=>[$saved,$mayReachDb]) {
        \Illuminate\Support\Facades\DB::$transactions=0;
        $session=new State(['ald1n.public_checkout.key'=>$key,
            'ald1n.public_checkout.last_key'=>$key,
            'ald1n.public_checkout.last_cart'=>$saved]);
        try { $service->place(new \Illuminate\Http\Request(new \App\Models\User(),$session),$input);
            throw new \RuntimeException('Unexpected checkout success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if($mayReachDb || !str_contains($e->getMessage(),'cart'))throw $e;
        } catch (\RuntimeException $e) {
            if(!$mayReachDb || $e->getMessage()!=='DB_BOUNDARY_SIMULATED')throw $e;
        }
        $want=$mayReachDb?1:0;
        if(\Illuminate\Support\Facades\DB::$transactions!==$want)throw new \RuntimeException($name.' reached DB unexpectedly');
        echo "PASS checkout $name\n";$passed++;
    }
    echo "CHECKOUT_EARLY_GATES=$passed\n";
}
