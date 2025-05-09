<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessFoodItemPhotoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
     public function toArray(Request $request): array
    {
       return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'business_food_photo_url' => asset($this->business_food_photo_url),
            'business_food_item_id' => (int) $this->business_food_item_id,
            //'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            //'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,


            //'business' => $this->business,
        ];
    }
}
