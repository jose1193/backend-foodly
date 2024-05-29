<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionBranch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'promotion_branch_uuid',
        'promotion_branch_title',
        'promotion_branch_description',
        'promotion_branch_start_date',
        'promotion_branch_end_date',
        'promotion_branch_type',
        'promotion_branch_status',
        'branch_id',
    ];


    public function branches()
    {
         return $this->belongsTo(BusinessBranch::class, 'branch_id');
    }

    public function promotionBranchesImages()
    {
        return $this->hasMany(PromotionBranchImage::class, 'promotion_branch_id');
    }
    
}
