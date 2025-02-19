<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessFoodItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'business_food_category_id',
        'name',
        'description',
        'versions',
        'prices',
        'favorites_count',
        'available',
    ];

    protected $casts = [
        'versions' => 'array',
        'prices' => 'array',
        'available' => 'boolean',
        
    ];


    
    
     public function businessFoodCategory()
    {

    return $this->belongsTo(BusinessFoodCategory::class);
 

    }

    public function foodItemReferencePhotos()
    {
        return $this->hasMany(BusinessFoodItemPhoto::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'business_food_item_favorites')
                ->withTimestamps();
    }
}
