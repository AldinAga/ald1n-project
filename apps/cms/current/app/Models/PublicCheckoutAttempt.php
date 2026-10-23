<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PublicCheckoutAttempt extends Model
{
    protected $fillable=['key_hash','request_fingerprint','receipt_token_ciphertext','order_id'];
    public function order(): BelongsTo {return $this->belongsTo(Order::class);}
}
