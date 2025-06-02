<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'address',
        'city',
        'country',
        'zip_code',
        'latitude',
        'longitude',
        'address_label_id',
        'principal',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'principal' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Generate UUID when creating
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        // Ensure only one principal address per user
        static::saving(function ($model) {
            if ($model->principal) {
                // Use a transaction to avoid constraint issues
                DB::transaction(function () use ($model) {
                    // First, set all other addresses for this user to non-principal
                    static::where('user_id', $model->user_id)
                        ->where('id', '!=', $model->id ?? 0)
                        ->update(['principal' => false]);
                });
            }
        });
    }

    /**
     * Get the user that owns the address.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the label for this address.
     */
    public function addressLabel()
    {
        return $this->belongsTo(AddressLabel::class, 'address_label_id');
    }

    /**
     * Scope to get principal address
     */
    public function scopePrincipal($query)
    {
        return $query->where('principal', true);
    }

    /**
     * Scope to get addresses by label ID
     */
    public function scopeByLabel($query, $labelId)
    {
        return $query->where('address_label_id', $labelId);
    }

    /**
     * Scope to get non-principal addresses
     */
    public function scopeSecondary($query)
    {
        return $query->where('principal', false);
    }
} 