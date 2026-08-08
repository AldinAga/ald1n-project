<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class UserGroup extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'status', 'category_access_mode', 'include_uncategorized', 'sort_order'];
    protected $casts = ['include_uncategorized' => 'boolean', 'sort_order' => 'integer'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_group_permissions', 'group_id', 'permission_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'user_group_categories', 'group_id', 'category_id');
    }
}
