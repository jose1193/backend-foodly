<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessFavorite extends Model
{
    use HasFactory;

    protected $table = 'business_favorites';

    protected $fillable = [
        'user_id',
        'business_id'
    ];
}