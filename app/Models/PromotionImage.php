<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_image_uuid',
        'promotion_image_path',
        'promotion_id',
    ];

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
