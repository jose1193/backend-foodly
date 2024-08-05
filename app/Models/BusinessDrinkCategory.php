<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessDrinkCategory extends Model
{
    use HasFactory;
     protected $fillable = ['uuid','business_menu_id','name'];

      public function businessMenu()
    {

    return $this->belongsTo(BusinessMenu::class);
 

    }

    public function businessDrinkItems()
    {

    return $this->hasMany(BusinessDrinkItem::class);
 

    }
}
