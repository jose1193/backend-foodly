<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    use HasFactory;
   protected $fillable = ['uuid','business_id', 'day', 'open_a', 'close_a', 'open_b', 'close_b'];


    public function business()
    {

    return $this->belongsTo(Business::class);
 

    }

}
