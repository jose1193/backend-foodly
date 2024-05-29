<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class BusinessBranch extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $fillable = [
        'branch_uuid',
        'branch_logo',
        'branch_name',
        'branch_email',
        'branch_phone',
        'branch_address',
        'branch_zipcode',
        'branch_city',
        'branch_country',
        'branch_website',
        'branch_latitude',
        'branch_longitude',
        'branch_about_us',
        'branch_additional_info',
        'business_id',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function coverImages()
    {
        return $this->hasMany(BranchCoverImage::class, 'branch_id');
    }

    public function promotionsbranches()
{
    return $this->hasMany(PromotionBranch::class, 'branch_id');
}

public function BranchServices()
    {
        return $this->belongsToMany(Service::class, 'branch_service');
    }

}
