<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CatalogSyncState extends Model
{
    protected $fillable = ['entity_type', 'entity_key', 'legacy_checksum', 'target_checksum', 'synced_at'];
    protected function casts(): array { return ['synced_at' => 'datetime']; }
}
