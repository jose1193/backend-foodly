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
            'category' => $this->category ? new CategoryResource($this->category) : null,
            'business_opening_hours' => $this->getBusinessOpeningHours(),
        ];
    }

    public function getBusinessOpeningHours()
    {
        if (!$this->relationLoaded('businessHours')) {
            if (!isset($this->businessHours) || $this->businessHours === null) {
                return null;
            }
        }

        $daysOfWeek = ['day_0', 'day_1', 'day_2', 'day_3', 'day_4', 'day_5', 'day_6'];
        $businessOpeningHours = [];

        foreach ($daysOfWeek as $day) {
            $businessOpeningHours[$day] = [
                'open_a' => $this->getOpenTime($day, 'open_a'),
                'close_a' => $this->getCloseTime($day, 'close_a'),
                'open_b' => $this->getOpenTime($day, 'open_b'),
                'close_b' => $this->getCloseTime($day, 'close_b'),
            ];
        }

        return $businessOpeningHours;
    }

    protected function getOpenTime($day, $time)
    {
        if ($this->businessHours === null) {
            return null;
        }
        return $this->businessHours->where('day', $day)->pluck($time)->first();
    }

    protected function getCloseTime($day, $time)
    {
        if ($this->businessHours === null) {
            return null;
        }
        return $this->businessHours->where('day', $day)->pluck($time)->first();
    }
} 