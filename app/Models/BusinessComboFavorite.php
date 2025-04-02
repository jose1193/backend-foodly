<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessComboFavorite extends Model
{
    use HasFactory;
    
    protected $table = 'business_combo_favorites';

    protected $fillable = [
        'user_id',
        'business_combo_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessCombo()
    {
        return $this->belongsTo(BusinessCombo::class);
    }
} 