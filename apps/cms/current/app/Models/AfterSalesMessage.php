<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AfterSalesMessage extends Model
{
    protected $fillable = ['after_sales_case_id', 'user_id', 'visibility', 'body'];
    public function case(): BelongsTo { return $this->belongsTo(AfterSalesCase::class, 'after_sales_case_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attachments(): HasMany { return $this->hasMany(AfterSalesAttachment::class, 'message_id'); }
}
