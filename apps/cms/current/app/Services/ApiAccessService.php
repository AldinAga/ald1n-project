<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class ApiAccessService
{
    /** @return list<string> */
    public function permissions(User $user): array
    {
        $permissions = $user->permissionSlugs();
        $token = $user->currentAccessToken();

        if ($token === null || $token->can('*')) {
            return $permissions;
        }

        return array_values(array_filter(
            $permissions,
            static fn (string $permission): bool => $token->can($permission),
        ));
    }

    /** @return array<string,bool> */
    public function features(User $user): array
    {
        $permissions = array_fill_keys($this->permissions($user), true);
        $wildcard = isset($permissions['*']);
        $has = static fn (string $permission): bool => $wildcard || isset($permissions[$permission]);

        return [
            'catalog' => $has('catalog.view'),
            'catalog_prices' => $has('catalog.view_prices'),
            'orders' => $has('orders.view_own'),
            'order_create' => $has('orders.create'),
            'order_cancel' => $has('orders.cancel_own'),
            'notifications' => app(ModuleVisibilityService::class)->enabled('notifications') && $has('notifications.view'),
            'commissions' => app(ModuleVisibilityService::class)->enabled('commissions') && $has('commissions.view_own'),
            'after_sales' => app(ModuleVisibilityService::class)->enabled('after_sales') && ($has('after_sales.view_own') || $has('after_sales.create')) ,
            'warranties' => app(ModuleVisibilityService::class)->enabled('warranties') && $has('warranties.view_own'),
            'field_operations' => app(ModuleVisibilityService::class)->enabled('field_operations') && $has('field_operations.view'),
            'profile' => true,
            'mobile_devices' => true,
            'push_registration' => true,
            'push_delivery' => (bool) config('mobile.push.enabled', false),
        ];
    }
}
