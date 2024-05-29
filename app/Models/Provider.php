<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = ['uuid','provider_id','provider','user_id','provider_avatar'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
