<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FieldWorkOrder extends Model
{
    protected $fillable = [
        'work_order_number', 'after_sales_action_id', 'field_service_team_id', 'status',
        'planned_start_at', 'planned_end_at', 'en_route_at', 'on_site_at', 'completed_at',
        'cancelled_at', 'status_by', 'customer_name_snapshot', 'customer_phone_snapshot',
        'service_address_snapshot', 'route_reference', 'public_note', 'internal_note',
        'completion_result', 'travel_km', 'travel_cost_rsd', 'labor_cost_rsd', 'parts_cost_rsd',
        'total_cost_rsd', 'cancellation_reason', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'planned_start_at' => 'datetime', 'planned_end_at' => 'datetime', 'en_route_at' => 'datetime',
            'on_site_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime',
            'travel_km' => 'decimal:2', 'travel_cost_rsd' => 'decimal:2', 'labor_cost_rsd' => 'decimal:2',
            'parts_cost_rsd' => 'decimal:2', 'total_cost_rsd' => 'decimal:2',
        ];
    }

    public function action(): BelongsTo { return $this->belongsTo(AfterSalesAction::class, 'after_sales_action_id'); }
    public function team(): BelongsTo { return $this->belongsTo(FieldServiceTeam::class, 'field_service_team_id'); }
    public function statusActor(): BelongsTo { return $this->belongsTo(User::class, 'status_by'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function attachments(): HasMany { return $this->hasMany(FieldWorkOrderAttachment::class)->orderBy('created_at'); }
    public function parts(): HasMany { return $this->hasMany(FieldWorkOrderPart::class)->orderBy('id'); }

    public function isTerminal(): bool { return in_array($this->status, ['completed', 'cancelled'], true); }

    /** @return array<string,string> */
    public static function statusLabels(): array
    {
        return [
            'planned' => 'Planiran', 'en_route' => 'Ekipa na putu', 'on_site' => 'Na lokaciji',
            'completed' => 'Završen', 'cancelled' => 'Otkazan',
        ];
    }
}
