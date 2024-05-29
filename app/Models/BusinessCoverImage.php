<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCoverImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_image_uuid',
        'business_image_path',
        'business_id',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
