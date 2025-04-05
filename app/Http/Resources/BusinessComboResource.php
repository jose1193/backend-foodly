<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessComboResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'menu_uuid' => $this->businessMenu->uuid ?? null,
            'business_uuid' => $this->businessMenu->business->business_uuid ?? null,
            'business_menu_id' => (int) $this->business_menu_id,
            'name' => $this->name,
            'description' => $this->description,
            'versions' => $this->versions,
            'prices' => [
            'regular' => $this->prices['regular'] !== null ? (double)$this->prices['regular'] : null,
            'medium' => $this->prices['medium'] !== null ? (double)$this->prices['medium'] : null,
            'big' => $this->prices['big'] !== null ? (double)$this->prices['big'] : null,
            ],
            'favorites_count' => (int) $this->favoritedBy()->count(),
            'available' => (boolean) $this->available,
            //'business_menu' => new BusinessMenuResource($this->whenLoaded('businessMenu')), // Incluye el menú si está cargado
            'business_combos_reference_photos' => BusinessComboPhotoResource::collection($this->businessComboPhotos), 
        ];
    }
}
