<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
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
        'business_menus' => $this->businessMenus->map(function ($menu) {
            return [
                'id' => (int) $menu->id,
                'uuid' => $menu->uuid,
                'business_uuid' => $this->business_uuid
            ];
        })->toArray(),
        'category_id' => $this->category ? $this->category->id : null,
        'category' => $this->category ? new CategoryResource($this->category): null,
        //'business_opening_hours' =>  BusinessHourResource::collection($this->businessHours),
        'business_opening_hours' => $this->getBusinessOpeningHours(),
        'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
        'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        'deleted_at' => $this->deleted_at ? $this->deleted_at->toDateTimeString() : null,
        'cover_images' => BusinessCoverImageResource::collection($this->coverImages), 
        'business_promotions' => $this->promotions->map(function ($promotion) {
    return [
        'id' => (int) $promotion->id,
        'uuid' => $promotion->uuid,
        'title' => $promotion->title,
        'sub_title' => $promotion->sub_title,
        'description' => $promotion->description,
        'start_date' => $promotion->start_date,
        'expire_date' => $promotion->expire_date,
        'versions' => $promotion->versions ?? null,  // Cambiado de $this->versions
        'prices' => isset($promotion->prices) ? [    // Cambiado de $this->prices
            'regular' => $promotion->prices['regular'] ?? null,
            'medium' => $promotion->prices['medium'] ?? null,
            'big' => $promotion->prices['big'] ?? null,
        ] : null,
        'available' => (boolean) $promotion->available,
        'business_promo_reference_media' => $promotion->promotionMedia->map(function ($media) {
            return [
                'id' => (int) $media->id,
                'uuid' => $media->uuid,
                'business_promo_item_id' => (int) $media->business_promo_item_id,
                'media_type' => $media->media_type,
                'business_promo_media_url' => $media->business_promo_media_url,
                'created_at' => $media->created_at,
                'updated_at' => $media->updated_at
            ];
        })->toArray()
    ];
})->toArray(),
        'business_branches' => BranchResource::collection($this->branches),
       
         

    ];

    
}
 // Suponiendo que este código forma parte de una clase o método donde se manejan los horarios de apertura
public function getBusinessOpeningHours()
{
    $daysOfWeek = ['day_0', 'day_1', 'day_2', 'day_3', 'day_4', 'day_5', 'day_6'];
    $businessOpeningHours = [];

    foreach ($daysOfWeek as $day) {
        $businessOpeningHours[$day] = [
            'open_a' => $this->getOpenTime($day, 'open_a'),
            'close_a' => $this->getCloseTime($day, 'close_a'),
            'open_b' => $this->getOpenTime($day, 'open_b'),
            'close_b' => $this->getCloseTime($day, 'close_b'),
        ];
    }

    return $businessOpeningHours;
}

protected function getOpenTime($day, $time)
{
    return $this->businessHours->where('day', $day)->pluck($time)->first();
}

protected function getCloseTime($day, $time)
{
    return $this->businessHours->where('day', $day)->pluck($time)->first();
}

 private function formatTime($time)
{
    if (!$time) {
        return null; // Devuelve null si el tiempo es nulo
    }

    return Carbon::createFromFormat('H:i:s', $time)->format('H:i');
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
