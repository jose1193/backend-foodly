<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use App\Http\Resources\BusinessResource;
use App\Http\Resources\BusinessFavoriteResource;

class BusinessFavoriteController extends Controller
{
    public function index()
    {
        $favorites = auth()->user()->favoriteBusiness;
        return response()->json([
            'favorite_businesses' => BusinessFavoriteResource::collection($favorites)
        ], 200);
    }

    public function toggle(Request $request, $businessUuid)
    {
        try {
            $business = Business::where('business_uuid', $businessUuid)->firstOrFail();
            $user = auth()->user();

            // Toggle favorite status
            if ($user->favoriteBusiness()->where('business_id', $business->id)->exists()) {
                $user->favoriteBusiness()->detach($business->id);
                $message = 'Business removed from favorites';
                $isFavorite = false;
            } else {
                $user->favoriteBusiness()->attach($business->id);
                $message = 'Business added to favorites';
                $isFavorite = true;
            }

            return response()->json([
                'is_favorite' => $isFavorite
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error processing favorite',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function check($businessUuid)
    {
        try {
            $business = Business::where('business_uuid', $businessUuid)->firstOrFail();
            $isFavorite = auth()->user()->favoriteBusiness()
                                      ->where('business_id', $business->id)
                                      ->exists();

            return response()->json(['is_favorite' => $isFavorite]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error checking favorite status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}