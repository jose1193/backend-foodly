<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubcategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'subcategory_uuid' => $this->subcategory_uuid,
            'subcategory_name' => $this->subcategory_name,
            
            'category_id' => (int) $this->category_id,
            //'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            //'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            //'category' => $this->category ? new CategoryResource($this->category): null,
            
        ];
    }
}
