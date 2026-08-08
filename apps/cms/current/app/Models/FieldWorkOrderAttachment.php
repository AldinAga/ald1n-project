<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FieldWorkOrderAttachment extends Model
{
    protected $fillable = [
        'field_work_order_id', 'uploaded_by', 'visibility', 'file_type', 'original_name',
        'stored_name', 'path', 'mime_type', 'size_bytes',
    ];

    public function workOrder(): BelongsTo { return $this->belongsTo(FieldWorkOrder::class, 'field_work_order_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}
