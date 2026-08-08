<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Permission extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'user_group_permissions', 'permission_id', 'group_id');
    }
}
