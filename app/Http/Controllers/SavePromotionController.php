<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use App\Http\Resources\SavedPromotionFavoriteResource;

class SavePromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $favorites = auth()->user()->favoritePromotions;
        return response()->json([
            'saved_promotions' => SavedPromotionFavoriteResource::collection($favorites)
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $promotion = Promotion::findOrFail($request->promotion_id);
        $user->favoritePromotions()->attach($promotion->id);

        return response()->json([
            'message' => 'Promotion saved successfully'
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    public function toggle(Request $request, $uuid)
    {
        try {
            $promotion = Promotion::where('uuid', $uuid)->firstOrFail();
            $user = auth()->user();

            // Toggle favorite status
            if ($user->favoritePromotions()->where('promotion_id', $promotion->id)->exists()) {
                $user->favoritePromotions()->detach($promotion->id);
                $message = 'Promotion removed from favorites';
                $isFavorite = false;
            } else {
                $user->favoritePromotions()->attach($promotion->id);
                $message = 'Promotion added to favorites';
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

    public function check($uuid)
    {
        try {
            $promotion = Promotion::where('uuid', $uuid)->firstOrFail();
            $isFavorite = auth()->user()->favoritePromotions()
                                      ->where('promotion_id', $promotion->id)
                                      ->exists();

            return response()->json(['is_favorite' => $isFavorite]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error checking favorite status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
