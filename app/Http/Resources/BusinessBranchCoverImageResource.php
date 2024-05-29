<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessBranchCoverImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
     public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_image_uuid' => $this->branch_image_uuid,
            'branch_image_path' => asset($this->branch_image_path),
            'branch_id' => $this->branch->id,
            //'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            //'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            //'branch' => $this->branch,
            
        ];
    }
}
