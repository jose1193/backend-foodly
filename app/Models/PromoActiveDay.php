<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoActiveDay  extends Model
{
    use HasFactory;
    protected $fillable = 
    ['promotion_id',
        'day_0',
        'day_1',
        'day_2',
        'day_3',
        'day_4',
        'day_5',
        'day_6'
    ];


    public function promotion()
    {

    return $this->belongsTo(Promotion::class);
 

    }

}
