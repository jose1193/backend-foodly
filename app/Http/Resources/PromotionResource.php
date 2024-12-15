<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
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
            //'prices' => $this->prices, 
            'prices' => [
            'regular' => $this->prices['regular'] !== null ? (double)$this->prices['regular'] : null,
            'medium' => $this->prices['medium'] !== null ? (double)$this->prices['medium'] : null,
            'big' => $this->prices['big'] !== null ? (double)$this->prices['big'] : null,
            ],
            'favorites_count' => (int) $this->favorites_count ?? 0,
            'available' => (boolean) $this->available,
            'promo_active_days' => $this->getPromoActiveDays(),
            'business_promo_reference_media' => PromotionMediaResource::collection($this->promotionMedia), 
            
           
            //'business_id' => $this->business->id,
            'business' => new BusinessResource($this->business),
            
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            //'deleted_at' => $this->deleted_at ? $this->deleted_at->toDateTimeString() : null,
          
           
            //'promotions_images' => $this->promotionImages->map(function ($image) {
            //return [
                //'id' => $image->id,
                //'promotion_image_uuid' => $image->promotion_image_uuid,
                //'promotion_image_path' => asset($image->promotion_image_path),
                //'promotion_id' => (int) $image->promotion_id,
                //'created_at' => $image->created_at ? $image->created_at->toDateTimeString() : null,
                //'updated_at' => $image->updated_at ? $image->updated_at->toDateTimeString() : null,
            //];
            //})->toArray(),
            //'business' => [
            //'id' => $this->business->id,
            //'user_id' => $this->business->user_id,
            //'business_uuid' => $this->business->business_uuid,
            //'business_logo' => $this->business->business_logo ? asset($this->business->business_logo) : null,
            //'business_name' => $this->business->business_name,
            //],

            
        ];


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
