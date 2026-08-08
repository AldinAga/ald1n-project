<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ReportSchedule extends Model
{
    protected $fillable = [
        'name', 'report_type', 'frequency', 'send_time', 'weekday', 'month_day', 'timezone',
        'recipients_json', 'filters_json', 'formats_json', 'is_active', 'next_run_at',
        'last_run_at', 'last_success_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'recipients_json' => 'array', 'filters_json' => 'array', 'formats_json' => 'array',
            'is_active' => 'boolean', 'weekday' => 'integer', 'month_day' => 'integer',
            'next_run_at' => 'datetime', 'last_run_at' => 'datetime', 'last_success_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function deliveries(): HasMany { return $this->hasMany(ReportDelivery::class)->latest('created_at'); }
}
