<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessComboPhoto extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'business_combos_id',
        'business_combos_photo_url',
    ];

    public function businessCombo()
    {
        return $this->belongsTo(BusinessCombo::class, 'business_combos_id');
    }
}
