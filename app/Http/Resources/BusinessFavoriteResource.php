<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessFavoriteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'business_uuid' => $this->business_uuid,
            'business_name' => $this->business_name,
            'business_logo' => asset($this->business_logo),
            'business_email' => $this->business_email,
            'business_phone' => $this->business_phone,
            'business_address' => $this->business_address,
            'business_city' => $this->business_city,
            'business_country' => $this->business_country,
            'business_zipcode' => $this->business_zipcode,
            'business_latitude' => (double) $this->business_latitude,
            'business_longitude' => (double) $this->business_longitude,
        ];
    }
} 