<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessDrinkItemPhoto extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'business_drink_item_id',
        'business_drink_photo_url',
    ];

    public function businessDrinkItem()
    {
        return $this->belongsTo(BusinessDrinkItem::class);
    }
}
