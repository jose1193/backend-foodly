<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessFoodItemResource extends JsonResource
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
        'menu_uuid' => $this->businessFoodCategory->businessMenu->uuid ?? null,
        'business_uuid' => $this->businessFoodCategory->businessMenu->business->business_uuid ?? null,
        'business_food_category_id' => (int) $this->business_food_category_id,
        'name' => $this->name,
        'description' => $this->description,
        'versions' => $this->versions ?? null,
        //'prices' => $this->prices, 
        'prices' => [
        'regular' => $this->prices['regular'] !== null ? (double)$this->prices['regular'] : null,
        'medium' => $this->prices['medium'] !== null ? (double)$this->prices['medium'] : null,
        'big' => $this->prices['big'] !== null ? (double)$this->prices['big'] : null,
        ],
        'favorites_count' => (int) $this->favoritedBy()->count(),
        'available' => (boolean) $this->available,
        'business_food_reference_photos' => BusinessFoodItemPhotoResource::collection($this->foodItemReferencePhotos),
        
    ];
}


   

}
