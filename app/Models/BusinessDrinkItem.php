<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessDrinkItem extends Model
{
    use HasFactory;

     protected $fillable = [
        'uuid',
        'business_drink_category_id',
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


    
     public function businessDrinkCategory()
    {

    return $this->belongsTo(BusinessDrinkCategory::class);
 

    }

    public function drinkItemReferencePhotos()
    {
        return $this->hasMany(BusinessDrinkItemPhoto::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'business_drink_item_favorites')
                ->withTimestamps();
    }
}
