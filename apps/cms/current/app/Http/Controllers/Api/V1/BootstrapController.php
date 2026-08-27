<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationPreferenceResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\MobileDevice;
use App\Services\ApiAccessService;
use App\Services\ExchangeRateService;
use App\Services\UserNotificationPreferenceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class BootstrapController extends Controller
{
    public function show(
        Request $request,
        ApiAccessService $access,
        UserNotificationPreferenceService $preferences,
        ExchangeRateService $exchangeRate,
    ): JsonResponse {
        $user = $request->user()->loadMissing(['role', 'group']);
        $preference = $preferences->for($user);
        $unreadCount = 0;
        $currency = [
            'base' => 'RSD',
            'alternate' => 'EUR',
            'eur_rsd_rate' => null,
            'rate_kind' => ExchangeRateService::RATE_KIND,
            'rate_label' => ExchangeRateService::RATE_LABEL,
            'provider' => null,
            'provider_date' => null,
            'is_stale' => true,
        ];

        try {
            $rateConfiguration = $exchangeRate->configuration();
            $currency['eur_rsd_rate'] = is_numeric($rateConfiguration['rate'] ?? null)
                ? (float) $rateConfiguration['rate']
                : null;
            $currency['provider'] = isset($rateConfiguration['provider'])
                ? (string) $rateConfiguration['provider']
                : null;
            $currency['provider_date'] = isset($rateConfiguration['provider_date'])
                ? (string) $rateConfiguration['provider_date']
                : null;
            $currency['is_stale'] = (bool) ($rateConfiguration['is_stale'] ?? true);
        } catch (Throwable) {
            // Presentation metadata is best-effort; bootstrap/auth must remain available.
        }

        try {
            $unreadCount = $user->unreadNotifications()->count();
        } catch (Throwable) {
            // Bootstrap remains available during partial legacy migrations.
        }

        try {
            $token = $user->currentAccessToken();
            $tokenId = $token instanceof Model ? (int) $token->getKey() : 0;
            if ($tokenId > 0) {
                MobileDevice::query()
                    ->where('user_id', $user->id)
                    ->where('personal_access_token_id', $tokenId)
                    ->whereNull('revoked_at')
                    ->update(['last_seen_at' => now()]);
            }
        } catch (Throwable) {
            // Device heartbeat is best-effort and must not block app startup.
        }

        return response()->json(['data' => [
            'user' => (new UserResource($user))->resolve($request),
            'permissions' => $access->permissions($user),
            'features' => $access->features($user),
            'notification_counts' => [
                'unread' => $unreadCount,
            ],
            'notification_preferences' => (new NotificationPreferenceResource($preference))->resolve($request),
            'app' => [
                'name' => (string) config('app.name'),
                'backend_version' => (string) config('app.version'),
                'api_version' => (string) config('mobile.api_version', 'v1'),
                'timezone' => (string) config('app.timezone'),
                'locale' => (string) config('app.locale'),
                'currency' => $currency,
                'android' => config('mobile.android'),
                'ios' => config('mobile.ios'),
            ],
        ]]);
    }
}
