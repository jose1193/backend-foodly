<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionBranchResource extends JsonResource
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
            'promotion_branch_uuid' => $this->promotion_branch_uuid,
            'promotion_branch_title' => $this->promotion_branch_title,
            'promotion_branch_description' => $this->promotion_branch_description,
            'promotion_branch_start_date' => $this->promotion_branch_start_date,
            'promotion_branch_end_date' => $this->promotion_branch_end_date,
            'promotion_branch_type' => $this->promotion_branch_type,
            'promotion_branch_status' => $this->promotion_branch_status,
            'branch_id' => (int) $this->branch_id,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toDateTimeString() : null,
            //'business_branch' => new BranchResource($this->branches),
            'branch_promotions_images' => PromotionBranchImageResource::collection($this->promotionBranchesImages), 
        ];
    }
}
