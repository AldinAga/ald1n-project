<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AfterSalesAttachment extends Model
{
    protected $fillable = ['after_sales_case_id', 'message_id', 'uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes'];
    public function case(): BelongsTo { return $this->belongsTo(AfterSalesCase::class, 'after_sales_case_id'); }
    public function message(): BelongsTo { return $this->belongsTo(AfterSalesMessage::class, 'message_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
