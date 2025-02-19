<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessMenu extends Model
{
    use HasFactory;
    protected $fillable = ['uuid','business_id'];


    public function business()
    {

    return $this->belongsTo(Business::class);
 

    }

    public function businessFoodCategories()
    {
        return $this->hasMany(BusinessFoodCategory::class);
    }

    
    public function businessDrinkCategories()
    {
        return $this->hasMany(BusinessDrinkCategory::class);
    }

public function businessCombo()
    {
        return $this->hasMany(BusinessCombo::class);
    }

public function favoritedBy()
{
    return $this->belongsToMany(User::class, 'business_menu_favorites')
                ->withTimestamps();
}

}

