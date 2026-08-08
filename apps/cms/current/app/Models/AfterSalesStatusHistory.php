<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AfterSalesStatusHistory extends Model
{
    public $timestamps = false;
    protected $table = 'after_sales_status_history';
    protected $fillable = ['after_sales_case_id', 'from_status', 'to_status', 'actor_id', 'note', 'metadata_json', 'created_at'];
    protected function casts(): array { return ['metadata_json' => 'array', 'created_at' => 'datetime']; }
    public function case(): BelongsTo { return $this->belongsTo(AfterSalesCase::class, 'after_sales_case_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
