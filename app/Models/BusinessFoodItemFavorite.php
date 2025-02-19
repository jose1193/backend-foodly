<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessFoodItemFavorite extends Model
{
    use HasFactory;
    protected $table = 'business_food_item_favorites';

    protected $fillable = [
        'user_id',
        'business_food_item_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessFoodItem()
    {
        return $this->belongsTo(BusinessFoodItem::class);
    }
}
