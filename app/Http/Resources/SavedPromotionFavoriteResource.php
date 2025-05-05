<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedPromotionFavoriteResource extends JsonResource
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
            'uuid' => $this->uuid,
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'expire_date' => $this->expire_date,
           
            'versions' => $this->versions ?? null,
            'prices' => [
                'regular' => $this->prices['regular'] !== null ? (double)$this->prices['regular'] : null,
                'medium' => $this->prices['medium'] !== null ? (double)$this->prices['medium'] : null,
                'big' => $this->prices['big'] !== null ? (double)$this->prices['big'] : null,
            ],
            'favorites_count' => (int)$this->favoritedBy()->count(),
            'available' => (boolean) $this->available,
            'promo_active_days' => $this->getPromoActiveDays(),
            'media_link' => $this->media_link,
            'business_promo_reference_media' => PromotionMediaResource::collection($this->promotionMedia), 
            'business' => [
                'id' => (int) $this->business->id,
                'business_uuid' => $this->business->business_uuid,
                'business_name' => $this->business->business_name,
                'business_logo' => asset($this->business->business_logo),
                'business_email' => $this->business_email,
                'business_phone' => $this->business_phone,
                'business_address' => $this->business->business_address,
                'business_city' => $this->business_city,
                'business_country' => $this->business_country,
                'business_zipcode' => $this->business_zipcode,
                'business_latitude' => (double) $this->business->business_latitude,
                'business_longitude' => (double) $this->business->business_longitude,
                'category' => $this->formatCategory(),
                'business_opening_hours' => $this->getBusinessOpeningHours(),
            ],
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }

    /**
     * Format the category data without subcategories.
     *
     * @return array|null
     */
    protected function formatCategory()
    {
        if (!$this->business->category) {
            return null;
        }

        return [
            'id' => (int) $this->business->category->id,
            'category_uuid' => $this->business->category->category_uuid,
            'category_name' => $this->business->category->category_name,
            'category_image_path' => asset($this->business->category->category_image_path),
        ];
    }

    /**
     * Get business opening hours data.
     *
     * @return array|null
     */
    public function getBusinessOpeningHours()
    {
        if (!$this->business->relationLoaded('businessHours')) {
            if (!isset($this->business->businessHours) || $this->business->businessHours === null) {
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

    /**
     * Get opening time for a specific day and time slot.
     *
     * @param string $day
     * @param string $time
     * @return mixed|null
     */
    protected function getOpenTime($day, $time)
    {
        if ($this->business->businessHours === null) {
            return null;
        }
        return $this->business->businessHours->where('day', $day)->pluck($time)->first();
    }

    /**
     * Get closing time for a specific day and time slot.
     *
     * @param string $day
     * @param string $time
     * @return mixed|null
     */
    protected function getCloseTime($day, $time)
    {
        if ($this->business->businessHours === null) {
            return null;
        }
        return $this->business->businessHours->where('day', $day)->pluck($time)->first();
    }

    private function getPromoActiveDays()
    {
        $days = ['day_0', 'day_1', 'day_2', 'day_3', 'day_4', 'day_5', 'day_6'];
        $activeDays = [];

        foreach ($days as $day) {
            $activeDays[$day] = (bool) $this->activeDay?->$day ?? false;
        }

        return $activeDays;
    }
} 