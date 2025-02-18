<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    use HasRoles;
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'username',
        'date_of_birth',
        'uuid',
        'email',
        'password',
        'phone',
        'address',   
        'zip_code',
        'city',
        'country',
        'gender',
        'profile_photo_path',
        'terms_and_conditions',
        'latitude',
        'longitude',     
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function category()
    {
        return $this->hasMany(Category::class);
    }

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    public function providers()
    {
        return $this->hasMany(Provider::class,'user_id');
    }

    public function favoriteBusiness()
    {
        return $this->belongsToMany(Business::class, 'business_favorites')
                ->withTimestamps();
    }
}
