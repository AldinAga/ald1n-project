<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PortalOrderLinkHistory extends Model
{
    public $timestamps = false;

    protected $table = 'portal_order_link_history';

    protected $fillable = [
        'order_id', 'from_user_id', 'to_user_id', 'changed_by', 'reason', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function previousUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function newUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
