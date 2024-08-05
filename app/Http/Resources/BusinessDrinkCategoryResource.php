<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessDrinkCategoryResource extends JsonResource
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
            'name' => $this->name,
            'business_menu_id' => (int) $this->business_menu_id,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            'items' => BusinessDrinkItemResource::collection($this->businessDrinkItems),
        ];
    }
}
