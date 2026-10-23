<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PublicOrderAccess extends Model
{
    protected $fillable=['order_id','token_hash','email_ciphertext','expires_at','revoked_at'];

    protected function casts(): array
    {
        return ['expires_at'=>'datetime','revoked_at'=>'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
