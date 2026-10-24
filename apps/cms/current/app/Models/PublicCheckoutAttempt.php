<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PublicCheckoutAttempt extends Model
{
    protected $fillable=[
        'key_hash','request_fingerprint','receipt_token_ciphertext','order_id',
        'terms_url','privacy_url','shipping_description','shipping_fee_rsd','merchant_policy_digest','terms_accepted_at',
    ];
    protected function casts(): array {
        return ['shipping_fee_rsd'=>'decimal:2','terms_accepted_at'=>'datetime'];
    }
    public function order(): BelongsTo {return $this->belongsTo(Order::class);}
}
