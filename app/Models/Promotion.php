<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'promotion_uuid',
        'promotion_title',
        'promotion_description',
        'promotion_start_date',
        'promotion_end_date',
        'promotion_type',
        'promotion_status',
        'business_id',

    ];


    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function promotionImages()
    {
        return $this->hasMany(PromotionImage::class, 'promotion_id');
    }
    
}
