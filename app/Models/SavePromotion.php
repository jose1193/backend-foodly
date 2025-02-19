<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavePromotion extends Model
{
    use HasFactory;
    protected $table = 'save_promotions';

    protected $fillable = [
        'user_id',
        'promotion_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
