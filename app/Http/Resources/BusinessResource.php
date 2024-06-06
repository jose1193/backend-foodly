<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
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
        'user_id' => (int) $this->user_id,
        'business_uuid' => $this->business_uuid,
        'business_logo' => asset($this->business_logo),
        'business_name' => $this->business_name,
        'business_email' => $this->business_email,
        'business_phone' => $this->business_phone,
        'business_about_us' => $this->business_about_us,
        'business_services' => ServiceResource::collection($this->services), 
        'business_additional_info' => $this->business_additional_info,
        'business_address' => $this->business_address,
        'business_zipcode' => $this->business_zipcode,
        'business_city' => $this->business_city,
        'business_country' => $this->business_country,
        'business_website' => $this->business_website,
        'business_latitude' => (double) $this->business_latitude,
        'business_longitude' => (double) $this->business_longitude,
        'business_website' => $this->business_website,
        'category_id' => $this->category ? $this->category->id : null,
        'category' => $this->category ? new CategoryResource($this->category): null,
        'business_opening_hours' => ['periods' => BusinessHourResource::collection($this->businessHours)],
        'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
        'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        'deleted_at' => $this->deleted_at ? $this->deleted_at->toDateTimeString() : null,
        'cover_images' => BusinessCoverImageResource::collection($this->coverImages), 
        'business_promotions' => PromotionResource::collection($this->promotions),
        'business_branches' => BranchResource::collection($this->branches),
       
        
    ];

    
}

    
        //protected function getBusinessPhoto()
        // {
        // Obtener la primera imagen de portada asociada al negocio
       // $coverImage = $this->coverImages->first();

        // Si existe una imagen de portada, retornar su ruta
        //if ($coverImage) {
           // return $coverImage->business_image_path;
        //}

        // Si no hay una imagen de portada, retornar null
       // return null;
        // }
}
