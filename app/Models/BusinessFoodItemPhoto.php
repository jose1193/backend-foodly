<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessFoodItemPhoto extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'business_food_item_id',
        'business_food_photo_url',
    ];

    public function businessFoodItem()
    {
        return $this->belongsTo(BusinessFoodItem::class);
    }
}
