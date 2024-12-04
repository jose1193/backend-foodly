<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionMedia extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'business_promo_media_url',
        'business_promo_item_id',
        'media_type'
    ];

    public function promotion() {
    return $this->belongsTo(Promotion::class, 'business_promo_item_id');
    }
}
