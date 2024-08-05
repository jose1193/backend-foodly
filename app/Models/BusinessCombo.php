<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCombo extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'business_menu_id',
        'name',
        'description',
        'versions',
        'prices',
        'favorites_count',
        'available',
    ];

    protected $casts = [
        'versions' => 'array',
        'prices' => 'array',
        'available' => 'boolean',
    ];

     public function businessMenu()
    {

    return $this->belongsTo(BusinessMenu::class);
 

    }

    public function businessComboPhotos()
    {
        return $this->hasMany(BusinessComboPhoto::class, 'business_combos_id');
    }
}
