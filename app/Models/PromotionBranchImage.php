<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionBranchImage extends Model
{
    use HasFactory;
    protected $fillable = [
        'promotion_branch_image_uuid',
        'promotion_branch_image_path',
        'promotion_branch_id',
    ];

    public function promotionBranch()
    {
        return $this->belongsTo(PromotionBranch::class);
    }

    
}
