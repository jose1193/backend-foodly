<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'sub_title',
        'description',
        'start_date',
        'expire_date',
        'media_link',
        'versions',
        'prices',
        'favorites_count',
        'available',
        'business_id',
    ];

    protected $casts = [
        'versions' => 'array',
        'prices' => 'array',
        'available' => 'boolean',
        
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function promotionMedia()
    {
        return $this->hasMany(PromotionMedia::class, 'business_promo_item_id');
    }

    public function activeDay() {
    return $this->hasOne(PromoActiveDay::class);
    }
    
}
