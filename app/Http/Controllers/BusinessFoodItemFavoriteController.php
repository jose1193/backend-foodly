<?php

namespace App\Http\Controllers;

use App\Models\BusinessFoodItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\BusinessFoodItemResource;

class BusinessFoodItemFavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $favorites = auth()->user()->favoriteFoodItems;
        return response()->json([
            'favorite_food_items' => BusinessFoodItemResource::collection($favorites)
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BusinessFoodItem $businessFoodItem): JsonResponse
    {
        $user = auth()->user();
        $businessFoodItem->favoritedBy()->attach($user->id);

        return response()->json([
            'message' => 'Food item added to favorites successfully'
        ]);
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessFoodItem $businessFoodItem): JsonResponse
    {
        $user = auth()->user();
        $businessFoodItem->favoritedBy()->detach($user->id);

        return response()->json([
            'message' => 'Food item removed from favorites successfully'
        ]);
    }

    public function toggle(Request $request, $uuid): JsonResponse
    {
        try {
            $foodItem = BusinessFoodItem::where('uuid', $uuid)->firstOrFail();
            $user = auth()->user();

            if ($user->favoriteFoodItems()->where('business_food_item_id', $foodItem->id)->exists()) {
                $user->favoriteFoodItems()->detach($foodItem->id);
                $isFavorite = false;
            } else {
                $user->favoriteFoodItems()->attach($foodItem->id);
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

    public function check($uuid): JsonResponse
    {
        try {
            $foodItem = BusinessFoodItem::where('uuid', $uuid)->firstOrFail();
            $isFavorite = auth()->user()->favoriteFoodItems()
                                      ->where('business_food_item_id', $foodItem->id)
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
