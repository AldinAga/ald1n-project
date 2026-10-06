<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

final class UserNotificationPreferenceService
{
    public function for(User $user): NotificationPreference
    {
        return $user->notificationPreference()->firstOrCreate([], $this->supported($this->defaults($user)));
    }

    /** @param array<string,mixed> $values */
    public function update(User $user, array $values): NotificationPreference
    {
        $allowed = array_keys($this->defaults($user));
        $values = array_intersect_key($values, array_fill_keys($allowed, true));

        $values = $this->supported($values);

        return $user->notificationPreference()->updateOrCreate([], $values);
    }

    /** @return array<string,mixed> */
    public function defaults(User $user): array
    {
        $staff = $user->hasRole('admin', 'superadmin');

        return [
            'in_app_enabled' => true,
            'email_enabled' => false,
            'push_enabled' => false,
            'shipment_tracking_channel' => 'both',
            'order_updates' => true,
            'payment_alerts' => true,
            'document_updates' => true,
            'after_sales_updates' => true,
            'warranty_updates' => true,
            'service_updates' => true,
            'receivable_updates' => true,
            'commission_updates' => true,
            'stock_alerts' => $staff,
            'daily_digest' => $staff,
        ];
    }

    /** @param array<string,mixed> $values @return array<string,mixed> */
    private function supported(array $values): array
    {
        if (!Schema::hasTable('notification_preferences')) {
            return $values;
        }

        $columns = array_flip(Schema::getColumnListing('notification_preferences'));

        return array_intersect_key($values, $columns);
    }
}
