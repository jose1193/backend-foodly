<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionBranchImageResource extends JsonResource
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
            'promotion_branch_image_uuid' => $this->promotion_branch_image_uuid,
            'promotion_branch_image_path' => asset($this->promotion_branch_image_path),
            'promotion_branch_id' => (int) $this->promotion_branch_id,
            //'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            //'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
           
        ];
    }
}
