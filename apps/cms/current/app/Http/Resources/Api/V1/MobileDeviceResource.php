<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MobileDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $token = $request->user()?->currentAccessToken();
        $currentTokenId = $token instanceof Model ? (int) $token->getKey() : 0;

        return [
            'id' => $this->id,
            'installation_id' => $this->installation_id,
            'platform' => $this->platform,
            'device_name' => $this->device_name,
            'push_provider' => $this->push_provider,
            'push_registered' => filled($this->push_token_hash),
            'app_version' => $this->app_version,
            'build_number' => $this->build_number,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'notifications_enabled' => (bool) $this->notifications_enabled,
            'is_current' => $currentTokenId > 0 && $currentTokenId === (int) ($this->personal_access_token_id ?? 0),
            'active' => $this->revoked_at === null,
            'last_seen_at' => optional($this->last_seen_at)->toIso8601String(),
            'revoked_at' => optional($this->revoked_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
