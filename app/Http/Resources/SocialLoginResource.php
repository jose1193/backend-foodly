<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialLoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
{
    return [
        'provider' => $this->resource['provider'], 
        'provider_id' => $this->resource['provider_id'], 
        'provider_avatar' => $this->resource['provider_avatar'],
        'uuid' => null, 
        'id' => null, 
        'photo' => null, 
        'name' => $this->resource['name'],
        'last_name' => null,
        'username' => $this->resource['username'], 
        'email' => $this->resource['email'],
        'email_verified_at' => null, 
        'date_of_birth' => null, 
        'address' => null, 
        'zip_code' => null, 
        'city' => null, 
        'country' => null, 
        'created_at' => null, 
        'updated_at' => null, 
        'deleted_at' => null, 
        'user_role' => null, 
        'role_id' => null,
        'social_provider' => [],
        'business' => [],
    
        
    ];
}

}
