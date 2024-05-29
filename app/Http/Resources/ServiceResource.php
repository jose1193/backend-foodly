<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'service_uuid' => $this->service_uuid,
            'service_name' => $this->service_name,
            //'service_description' => $this->service_description,
            //'service_image_path' => asset($this->service_image_path), 
            //'user_id' => $this->user->id,
            //'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            //'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
           
        ];
    }
}
