<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('slug')->all()),
            'account_status' => $this->account_status?->value,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'profile' => $this->whenLoaded('profile', fn () => [
                'display_name' => $this->profile?->display_name,
                'country_code' => $this->profile?->country_code,
                'timezone' => $this->profile?->timezone,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
