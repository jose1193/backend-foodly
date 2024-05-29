<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ForgotPasswordUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       return [
            'email' => $this->email,
            'token' => $this->token,
            'pin' => $this->pin,
            'pin_verified_at' => $this->pin_verified_at ? $this->pin_verified_at->toDateTimeString() : 'No Verified',
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            
        ];
    }
}
