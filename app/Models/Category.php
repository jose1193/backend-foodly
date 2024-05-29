<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
     protected $fillable = [
       'category_uuid', 'category_name', 'category_description','category_image_path','user_id'
    ];



    public function user()
{
return $this->belongsTo(User::class);
}



 public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }



    public function business()
    {
        return $this->hasMany(Business::class);
    }

    
}
