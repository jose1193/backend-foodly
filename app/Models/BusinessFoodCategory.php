<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessFoodCategory extends Model
{
    use HasFactory;
     protected $fillable = ['uuid','business_menu_id','name'];

      public function businessMenu()
    {

    return $this->belongsTo(BusinessMenu::class);
 

    }

    public function businessFoodItems()
    {

    return $this->hasMany(BusinessFoodItem::class);
 

    }
}
