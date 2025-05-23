<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Helpers\UserHelper;
use Illuminate\Support\Facades\Cache;

class UserResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'photo' => Cache::remember("user.{$this->id}.photo", now()->addMinutes(60), function () {
            return $this->profile_photo_path ? asset($this->profile_photo_path) : UserHelper::generateAvatarUrl($this->name);
            }),
            'name' => $this->name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'date_of_birth' => $this->date_of_birth,
            'phone' => $this->phone,
            'address' => $this->address,
            'zip_code' => $this->zip_code,
            'city' => $this->city,
            'country' => $this->country,
            'latitude' => (double) $this->latitude,
            'longitude' => (double) $this->longitude,
            'terms_and_conditions' => (boolean) $this->terms_and_conditions,
            'gender' => $this->gender,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'user_role' => $this->roles->pluck('name')->first() ?? null,
            'role_id' => $this->roles->pluck('id')->first() ?? null,
            'social_provider' => $this->providers->isNotEmpty() ? $this->providers : [],
            'business' => BusinessResource::collection($this->businesses),
            'favorite_businesses' => $this->favoriteBusiness->pluck('business_uuid'),
            'favorite_menus' => $this->favoriteMenus->pluck('uuid'), 
            'favorite_items' => [
                ...$this->favoriteFoodItems->pluck('uuid'),
                ...$this->favoriteDrinkItems->pluck('uuid'),
                ...$this->favoriteCombos->pluck('uuid'),
                
            ],
            'favorite_combos' => $this->favoriteCombos->pluck('uuid'),
            'saved_promotions' => $this->favoritePromotions->pluck('uuid'),
            //'business' => $this->businesses && $this->businesses->isNotEmpty() ? BusinessResource::collection($this->businesses) : [],
            'followers' => $this->followers->pluck('uuid'),
            'followers_length' => (int) $this->followers()->count(),
            'following' => $this->following->pluck('uuid'),
            'following_length' => (int) $this->following()->count(),
        ];
    }
}
