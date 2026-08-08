<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Throwable;

final class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    private bool $roleSlugResolved = false;
    private ?string $resolvedRoleSlug = null;
    private bool $permissionSlugsResolved = false;
    /** @var array<string,true> */
    private array $resolvedPermissionSlugs = [];

    protected $fillable = [
        'role_id', 'user_group_id', 'username', 'email', 'password_hash', 'first_name', 'last_name',
        'phone', 'address', 'city', 'postal_code', 'status', 'approved_by', 'approved_at', 'last_login_at',
        'password_changed_at', 'email_verified_at', 'portal_activated_at',
    ];

    protected $hidden = ['password_hash', 'remember_token'];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'portal_activated_at' => 'datetime',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }

    public function hasRole(string ...$roles): bool
    {
        try {
            return in_array($this->resolvedRoleSlug(), $roles, true);
        } catch (Throwable) {
            return false;
        }
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissionSlugs();

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    /** @return list<string> */
    public function permissionSlugs(): array
    {
        try {
            if ($this->status !== 'active') {
                return [];
            }

            if ($this->hasRole('admin', 'superadmin')) {
                if (!$this->permissionSlugsResolved) {
                    $slugs = DB::table('permissions')
                        ->orderBy('slug')
                        ->pluck('slug')
                        ->map(static fn ($slug): string => (string) $slug)
                        ->values()
                        ->all();
                    $this->resolvedPermissionSlugs = array_fill_keys($slugs !== [] ? $slugs : ['*'], true);
                    $this->permissionSlugsResolved = true;
                }

                return array_keys($this->resolvedPermissionSlugs);
            }

            if ($this->user_group_id === null) {
                return [];
            }

            if (!$this->permissionSlugsResolved) {
                $slugs = DB::table('user_groups')
                    ->join('user_group_permissions', 'user_group_permissions.group_id', '=', 'user_groups.id')
                    ->join('permissions', 'permissions.id', '=', 'user_group_permissions.permission_id')
                    ->where('user_groups.id', (int) $this->user_group_id)
                    ->where('user_groups.status', 'active')
                    ->orderBy('permissions.slug')
                    ->pluck('permissions.slug')
                    ->map(static fn ($slug): string => (string) $slug)
                    ->all();
                $this->resolvedPermissionSlugs = array_fill_keys($slugs, true);
                $this->permissionSlugsResolved = true;
            }

            return array_keys($this->resolvedPermissionSlugs);
        } catch (Throwable) {
            return $this->hasRole('admin', 'superadmin') ? ['*'] : [];
        }
    }

    public function clearResolvedAccessCache(): void
    {
        $this->roleSlugResolved = false;
        $this->resolvedRoleSlug = null;
        $this->permissionSlugsResolved = false;
        $this->resolvedPermissionSlugs = [];
    }

    private function resolvedRoleSlug(): ?string
    {
        if ($this->roleSlugResolved) {
            return $this->resolvedRoleSlug;
        }

        $slug = $this->relationLoaded('role') ? $this->role?->slug : $this->role()->value('slug');
        $this->resolvedRoleSlug = $slug !== null ? (string) $slug : null;
        $this->roleSlugResolved = true;

        return $this->resolvedRoleSlug;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function suppliedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'supplier_user_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(OrderCommission::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function mobileDevices(): HasMany
    {
        return $this->hasMany(MobileDevice::class);
    }

    public function activationTokens(): HasMany
    {
        return $this->hasMany(UserActivationToken::class);
    }

    public function latestActivationToken(): HasOne
    {
        return $this->hasOne(UserActivationToken::class)->latestOfMany('created_at');
    }

    public function loginSessions(): HasMany
    {
        return $this->hasMany(UserLoginSession::class);
    }

    public function portalConversations(): HasMany
    {
        return $this->hasMany(PortalConversation::class, 'user_id');
    }

    public function automationRuns(): HasMany
    {
        return $this->hasMany(AutomationRun::class, 'triggered_by');
    }

    public function submittedPayments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'submitted_by');
    }

    public function displayName(): string
    {
        $name = trim((string) $this->first_name.' '.(string) $this->last_name);
        if ($name !== '') {
            return $name;
        }

        $username = trim((string) $this->username);
        return $username !== '' ? $username : 'Korisnik';
    }

    public function displayInitial(): string
    {
        $name = trim($this->displayName());
        if ($name === '') {
            return 'A';
        }

        return function_exists('mb_substr')
            ? (string) mb_substr($name, 0, 1, 'UTF-8')
            : (string) substr($name, 0, 1);
    }

    public function roleName(string $fallback = 'Korisnik'): string
    {
        try {
            $name = $this->relationLoaded('role')
                ? $this->role?->name
                : $this->role()->value('name');
            $name = trim((string) $name);

            return $name !== '' ? $name : $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }
}
