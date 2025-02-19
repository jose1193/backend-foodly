<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessDrinkItemFavorite extends Model
{
    use HasFactory;
    protected $table = 'business_drink_item_favorites';

    protected $fillable = [
        'user_id',
        'business_drink_item_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessDrinkItem()
    {
        return $this->belongsTo(BusinessDrinkItem::class);
    }
}
