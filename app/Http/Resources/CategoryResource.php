<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        //$subcategories = $this->subcategories()->exists() ? $this->subcategories : [];
        return [
            'id' => (int) $this->id,
            'category_uuid' => $this->category_uuid,
            'category_name' => $this->category_name,
            //'category_description' => $this->category_description,
            'category_image_path' => asset($this->category_image_path),
            //'user_id' => $this->user->id,
            //'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            //'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            'subcategories' => SubcategoryResource::collection($this->subcategories),
            //'subcategories' => $subcategories
        ];
    }
}
