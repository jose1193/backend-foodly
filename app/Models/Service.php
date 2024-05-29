<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['service_uuid', 'service_name', 'service_description','service_image_path','user_id'];

    public function user()
{
return $this->belongsTo(User::class);
}

    public function businesses()
    {
        return $this->belongsToMany(Business::class, 'business_service');
    }

    public function branches()
    {
        return $this->belongsToMany(BusinessBranch::class, 'branch_service');
    }
}
