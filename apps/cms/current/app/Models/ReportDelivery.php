<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ReportDelivery extends Model
{
    protected $fillable = [
        'report_schedule_id', 'report_type', 'recipient_email', 'recipient_name', 'filters_json',
        'formats_json', 'period_from', 'period_to', 'status', 'attempt_count', 'scheduled_for',
        'last_attempt_at', 'sent_at', 'last_error', 'attachment_names_json', 'dedupe_key',
    ];

    protected function casts(): array
    {
        return [
            'filters_json' => 'array', 'formats_json' => 'array', 'attachment_names_json' => 'array',
            'period_from' => 'date', 'period_to' => 'date', 'attempt_count' => 'integer',
            'scheduled_for' => 'datetime', 'last_attempt_at' => 'datetime', 'sent_at' => 'datetime',
        ];
    }

    public function schedule(): BelongsTo { return $this->belongsTo(ReportSchedule::class, 'report_schedule_id'); }
}
