<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'zip_code' => $this->zip_code,
            'latitude' => (double) $this->latitude,
            'longitude' => (double) $this->longitude,
            'address_label_id' => $this->address_label_id,
            'label' => [
                'id' => $this->addressLabel->id,
                'uuid' => $this->addressLabel->uuid,
                'name' => $this->addressLabel->name,
                'description' => $this->addressLabel->description,
                'icon' => $this->addressLabel->icon,
            ],
            'principal' => (boolean) $this->principal,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 