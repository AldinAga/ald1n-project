<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FieldServiceTeam extends Model
{
    protected $fillable = [
        'code', 'name', 'team_type', 'contact_person', 'phone', 'email', 'vehicle_registration',
        'service_area', 'is_active', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function workOrders(): HasMany { return $this->hasMany(FieldWorkOrder::class); }

    /** @return array<string,string> */
    public static function typeLabels(): array
    {
        return ['internal' => 'Interna ekipa', 'external' => 'Spoljni partner / servis'];
    }
}
