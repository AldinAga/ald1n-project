<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommissionStatusHistory extends Model
{
    public $timestamps = false;
    protected $table = 'commission_status_history';
    protected $fillable = ['commission_id', 'order_id', 'changed_by', 'old_status', 'new_status', 'note', 'metadata_json', 'created_at'];
    protected function casts(): array { return ['metadata_json' => 'array', 'created_at' => 'datetime']; }
    public function commission(): BelongsTo { return $this->belongsTo(OrderCommission::class, 'commission_id'); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
