<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\PublicOrderAccess;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PublicOrderAccessService
{
    /**
     * Called only after a successful committed-within-the-same-transaction
     * anonymous retail checkout. Never persist or log plaintext bearer tokens.
     */
    public function issue(Order $order, string $email): string
    {
        $email=mb_strtolower(trim($email));
        if (filter_var($email,FILTER_VALIDATE_EMAIL)===false || strlen($email)>254) {
            throw ValidationException::withMessages(['email'=>'Unesi ispravnu email adresu.']);
        }
        return DB::transaction(function () use ($order,$email): string {
            $locked=Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->sales_channel!=='public_retail' || $locked->user_id!==null) {
                throw ValidationException::withMessages(['order'=>'Nevažeći javni prodajni tok.']);
            }
            if (PublicOrderAccess::query()->where('order_id',$locked->id)->exists()) {
                throw ValidationException::withMessages(['order'=>'Pristup porudžbini je već izdat.']);
            }
            $plain=bin2hex(random_bytes(32));
            PublicOrderAccess::query()->create([
                'order_id'=>$locked->id,
                'token_hash'=>hash('sha256',$plain),
                'email_ciphertext'=>Crypt::encryptString($email),
                'expires_at'=>now()->addDays(60),
            ]);
            return $plain;
        },3);
    }

    public function order(string $token): Order
    {
        abort_unless(preg_match('/\A[a-f0-9]{64}\z/D',$token)===1,404);
        $access=PublicOrderAccess::query()->where('token_hash',hash('sha256',$token))
            ->whereNull('revoked_at')->where('expires_at','>',now())->first();
        abort_unless($access instanceof PublicOrderAccess,404);
        $order=Order::query()->whereKey($access->order_id)
            ->where('sales_channel','public_retail')
            ->whereNull('user_id')->whereNull('purged_at')->first();
        abort_unless($order instanceof Order,404);
        return $order;
    }

    /**
     * Called by existing permissions-gated admin routes, never anonymously.
     */
    public function revoke(Order $order): void
    {
        abort_unless($order->sales_channel==='public_retail',404);
        PublicOrderAccess::query()->where('order_id',$order->id)
            ->whereNull('revoked_at')->update(['revoked_at'=>now()]);
    }
}
