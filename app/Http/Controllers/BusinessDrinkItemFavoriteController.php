<?php

namespace App\Http\Controllers;

use App\Models\BusinessDrinkItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\BusinessDrinkItemResource;

class BusinessDrinkItemFavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $favorites = auth()->user()->favoriteDrinkItems;
        return response()->json([
            'favorite_drink_items' => BusinessDrinkItemResource::collection($favorites)
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BusinessDrinkItem $businessDrinkItem): JsonResponse
    {
        $user = auth()->user();
        $businessDrinkItem->favoritedBy()->attach($user->id);

        return response()->json([
            'message' => 'Drink item added to favorites successfully'
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
    public function destroy(BusinessDrinkItem $businessDrinkItem): JsonResponse
    {
        $user = auth()->user();
        $businessDrinkItem->favoritedBy()->detach($user->id);

        return response()->json([
            'message' => 'Drink item removed from favorites successfully'
        ]);
    }

    public function toggle(Request $request, $uuid): JsonResponse
    {
        try {
            $drinkItem = BusinessDrinkItem::where('uuid', $uuid)->firstOrFail();
            $user = auth()->user();

            if ($user->favoriteDrinkItems()->where('business_drink_item_id', $drinkItem->id)->exists()) {
                $user->favoriteDrinkItems()->detach($drinkItem->id);
                $isFavorite = false;
            } else {
                $user->favoriteDrinkItems()->attach($drinkItem->id);
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
            $drinkItem = BusinessDrinkItem::where('uuid', $uuid)->firstOrFail();
            $isFavorite = auth()->user()->favoriteDrinkItems()
                                      ->where('business_drink_item_id', $drinkItem->id)
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
