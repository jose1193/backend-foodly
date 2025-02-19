<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessDrinkItemResource extends JsonResource
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
            'business_drink_category_id' => (int) $this->business_drink_category_id,
            'name' => $this->name,
            'description' => $this->description,
            
            //'prices' => $this->prices,
            'versions' => $this->versions ?? null,
            //'prices' => $this->prices, 
            'prices' => [
            'regular' => $this->prices['regular'] !== null ? (double)$this->prices['regular'] : null,
            'medium' => $this->prices['medium'] !== null ? (double)$this->prices['medium'] : null,
            'big' => $this->prices['big'] !== null ? (double)$this->prices['big'] : null,
            ],
            'favorites_count' => $this->favorites_count,
            'available' => (boolean) $this->available,
            //'business_menu' => new BusinessMenuResource($this->whenLoaded('businessMenu')),
            'business_drink_reference_photos' => BusinessDrinkItemPhotoResource::collection($this->drinkItemReferencePhotos), 
            'favorites_count' => (int) $this->favoritedBy()->count(),
        ];
    }
}
