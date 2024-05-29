<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchCoverImage extends Model
{
    use HasFactory;
    protected $fillable = [
        'branch_image_uuid',
        'branch_image_path',
        'branch_id',
    ];

    public function branch()
    {
        return $this->belongsTo(BusinessBranch::class);
    }
}
