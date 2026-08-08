<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderStatusHistory extends Model
{
    public $timestamps = false;
    protected $table = 'order_status_history';
    protected $fillable = ['order_id', 'changed_by', 'old_status', 'new_status', 'note', 'created_at'];
    protected function casts(): array { return ['created_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
