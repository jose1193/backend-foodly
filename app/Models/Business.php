<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory;
    use SoftDeletes;

     protected $fillable = [
        'business_uuid',
        'business_logo',
        'business_name',
        'business_email',
        'business_phone',
        'business_address',
        'business_zipcode',
        'business_city',
        'business_country',
        'business_website',
        'business_about_us',
        
        'business_additional_info',
        'business_latitude',
        'business_longitude',
        
        'category_id',
        'user_id'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coverImages()
    {
        return $this->hasMany(BusinessCoverImage::class, 'business_id');
    }

    public function branches()
    {
        return $this->hasMany(BusinessBranch::class, 'business_id');
    }

    public function promotions()
{
    return $this->hasMany(Promotion::class, 'business_id');
}


public function services()
    {
        return $this->belongsToMany(Service::class, 'business_service');
    }
}
