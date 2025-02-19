<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessMenuResource extends JsonResource
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
            'business_id' => (int) $this->business_id,
            'business_uuid' => $this->business->business_uuid, 
            'business_food_categories' => BusinessFoodCategoryResource::collection($this->businessFoodCategories),
            'business_drink_categories' => BusinessDrinkCategoryResource::collection($this->businessDrinkCategories),
            'business_combos' => BusinessComboResource::collection($this->businessCombo),
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            'favorites_count' => (int)$this->favoritedBy()->count(),
        ];
    }
}
