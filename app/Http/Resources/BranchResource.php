<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    
    public function toArray(Request $request): array
    {
       
        return [
            'id' =>  $this->id,
            'branch_uuid' => $this->branch_uuid,
            'branch_logo' => asset($this->branch_logo),
            'branch_name' => $this->branch_name,
            'branch_email' => $this->branch_email,
            'branch_phone' => $this->branch_phone,
            'branch_about_us' => $this->branch_about_us,
            'branch_services' => ServiceResource::collection($this->BranchServices),
            'branch_opening_hours' => ['periods' => BranchHourResource::collection($this->branchHours)], 
            'branch_additional_info' => $this->branch_about_us,
            'branch_address' => $this->branch_address,
            'branch_zipcode' => $this->branch_zipcode,
            'branch_city' => $this->branch_city,
            'branch_country' => $this->branch_country,
            'branch_website' => $this->branch_website,
            'branch_latitude' => (double) $this->branch_latitude,
            'branch_longitude' => (double) $this->branch_longitude,
            'business_id' => $this->business->id,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toDateTimeString() : null,
            'branch_cover_images' => BusinessBranchCoverImageResource::collection($this->coverImages),
            'branch_promotions' => PromotionBranchResource::collection($this->promotionsbranches),
        ];
    }
}
