<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'promotion_uuid' => $this->promotion_uuid,
            'promotion_title' => $this->promotion_title,
            'promotion_description' => $this->promotion_description,
            'promotion_start_date' => $this->promotion_start_date,
            'promotion_end_date' => $this->promotion_end_date,
            'promotion_type' => $this->promotion_type,
            'promotion_status' => $this->promotion_status,
            'business_id' => $this->business->id,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toDateTimeString() : null,
          
            'business_promotions_images' => PromotionImageResource::collection($this->promotionImages), 
            
            
            //'promotions_images' => $this->promotionImages->map(function ($image) {
            //return [
                //'id' => $image->id,
                //'promotion_image_uuid' => $image->promotion_image_uuid,
                //'promotion_image_path' => asset($image->promotion_image_path),
                //'promotion_id' => (int) $image->promotion_id,
                //'created_at' => $image->created_at ? $image->created_at->toDateTimeString() : null,
                //'updated_at' => $image->updated_at ? $image->updated_at->toDateTimeString() : null,
            //];
            //})->toArray(),
            //'business' => [
            //'id' => $this->business->id,
            //'user_id' => $this->business->user_id,
            //'business_uuid' => $this->business->business_uuid,
            //'business_logo' => $this->business->business_logo ? asset($this->business->business_logo) : null,
            //'business_name' => $this->business->business_name,
            //],
        ];
    }
}
