<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CourierService;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CourierDirectoryService
{
    public function __construct(private readonly AuditLogger $audit) {}
    /** @return Collection<int,CourierService> */
    public function active(): Collection
    {
        if (!Schema::hasTable('courier_services')) return new Collection();
        return CourierService::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function defaultCourier(): ?CourierService
    {
        if (!Schema::hasTable('courier_services')) return null;
        return CourierService::query()
            ->active()
            ->where('is_default', true)
            ->orderBy('sort_order')
            ->first()
            ?? CourierService::query()->active()->orderBy('sort_order')->orderBy('name')->first();
    }
    /** @return Collection<int,CourierService> */
    public function all(): Collection
    {
        if (!Schema::hasTable('courier_services')) return new Collection();
        return CourierService::query()->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name')->get();
    }

    /** @param array<string,mixed> $data */
    public function create(array $data, User $actor): CourierService
    {
        $this->assertSuperAdmin($actor);
        return DB::transaction(function () use ($data, $actor): CourierService {
            $isDefault = (bool) ($data['is_default'] ?? false);
            $isActive = $isDefault ? true : (bool) ($data['is_active'] ?? false);
            if ($isDefault) CourierService::query()->where('is_default', true)->update(['is_default' => false, 'updated_by' => $actor->id, 'updated_at' => now()]);
            $courier = CourierService::query()->create([
                'name' => trim((string) $data['name']),
                'slug' => $this->uniqueSlug((string) $data['name']),
                'tracking_url' => trim((string) $data['tracking_url']),
                'is_active' => $isActive,
                'is_default' => $isDefault,
                'sort_order' => (int) ($data['sort_order'] ?? 100),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $this->ensureDefault($actor);
            $this->audit->log('courier.created', 'Dodata kurirska služba '.$courier->name, $courier, after: $courier->toArray(), user: $actor);
            return $courier->fresh() ?? $courier;
        }, 5);
    }

    /** @param array<string,mixed> $data */
    public function update(CourierService $courier, array $data, User $actor): CourierService
    {
        $this->assertSuperAdmin($actor);
        return DB::transaction(function () use ($courier, $data, $actor): CourierService {
            $locked = CourierService::query()->lockForUpdate()->findOrFail($courier->id);
            $before = $locked->toArray();
            $isDefault = (bool) ($data['is_default'] ?? false);
            $isActive = $isDefault ? true : (bool) ($data['is_active'] ?? false);
            if (!$isActive && (int) CourierService::query()->where('is_active', true)->where('id', '<>', $locked->id)->count() === 0) {
                throw ValidationException::withMessages(['is_active' => 'Najmanje jedna kurirska služba mora ostati aktivna.']);
            }
            if ($isDefault) CourierService::query()->where('id', '<>', $locked->id)->where('is_default', true)->update(['is_default' => false, 'updated_by' => $actor->id, 'updated_at' => now()]);
            $locked->update([
                'name' => trim((string) $data['name']),
                'slug' => $this->uniqueSlug((string) $data['name'], (int) $locked->id),
                'tracking_url' => trim((string) $data['tracking_url']),
                'is_active' => $isActive,
                'is_default' => $isDefault,
                'sort_order' => (int) ($data['sort_order'] ?? $locked->sort_order),
                'updated_by' => $actor->id,
            ]);
            $this->ensureDefault($actor);
            $locked->refresh();
            $this->audit->log('courier.updated', 'Izmenjena kurirska služba '.$locked->name, $locked, before: $before, after: $locked->toArray(), user: $actor);
            return $locked;
        }, 5);
    }

    private function ensureDefault(User $actor): void
    {
        if (CourierService::query()->where('is_active', true)->where('is_default', true)->exists()) return;
        $first = CourierService::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->lockForUpdate()->first();
        if ($first instanceof CourierService) $first->update(['is_default' => true, 'updated_by' => $actor->id]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug(trim($name));
        if ($base === '') $base = 'courier';
        $slug = $base;
        $suffix = 2;
        while (CourierService::query()->when($ignoreId !== null, fn ($q) => $q->where('id', '<>', $ignoreId))->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }
        return $slug;
    }

    private function assertSuperAdmin(User $actor): void
    {
        abort_unless($actor->hasRole('superadmin'), 403);
    }
}
