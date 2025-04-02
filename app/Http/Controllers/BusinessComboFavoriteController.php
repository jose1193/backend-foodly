<?php

namespace App\Http\Controllers;

use App\Models\BusinessCombo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\BusinessComboResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\User;

class BusinessComboFavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $favorites = auth()->user()->favoriteCombos;
        return response()->json([
            'favorite_combos' => BusinessComboResource::collection($favorites)
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BusinessCombo $businessCombo): JsonResponse
    {
        $user = auth()->user();
        $businessCombo->favoritedBy()->attach($user->id);
        
        return response()->json([
            'message' => 'Combo added to favorites successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessCombo $businessCombo): JsonResponse
    {
        $user = auth()->user();
        $businessCombo->favoritedBy()->detach($user->id);

        return response()->json([
            'message' => 'Combo removed from favorites successfully'
        ]);
    }

    /**
     * Toggle favorite status
     */
    public function toggle(Request $request, $uuid): JsonResponse
    {
        try {
            $combo = BusinessCombo::where('uuid', $uuid)->firstOrFail();
            $user = auth()->user();

            if ($user->favoriteCombos()->where('business_combo_id', $combo->id)->exists()) {
                $user->favoriteCombos()->detach($combo->id);
                $isFavorite = false;
            } else {
                $user->favoriteCombos()->attach($combo->id);
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

    /**
     * Check if combo is favorited
     */
    public function check($uuid): JsonResponse
    {
        try {
            $combo = BusinessCombo::where('uuid', $uuid)->firstOrFail();
            $isFavorite = auth()->user()->favoriteCombos()
                                      ->where('business_combo_id', $combo->id)
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