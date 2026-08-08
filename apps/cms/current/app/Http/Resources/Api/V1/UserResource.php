<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'name' => $this->displayName(),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'status' => $this->status,
            'role' => $this->whenLoaded('role', fn (): ?array => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'slug' => $this->role->slug,
            ] : null),
            'group' => $this->whenLoaded('group', fn (): ?array => $this->group ? [
                'id' => $this->group->id,
                'name' => $this->group->name,
                'slug' => $this->group->slug,
            ] : null),
        ];
    }
}
