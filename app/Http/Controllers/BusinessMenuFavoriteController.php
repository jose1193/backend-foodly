<?php

namespace App\Http\Controllers;

use App\Models\BusinessMenu;
use Illuminate\Http\Request;
use App\Http\Resources\BusinessMenuResource;

class BusinessMenuFavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $favorites = auth()->user()->favoriteMenus;
        return response()->json([
            'favorite_menus' => BusinessMenuResource::collection($favorites)
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function destroy(string $id)
    {
        //
    }

    public function toggle(Request $request, $uuid)
    {
        try {
            $menu = BusinessMenu::where('uuid', $uuid)->firstOrFail();
            $user = auth()->user();

            // Toggle favorite status
            if ($user->favoriteMenus()->where('business_menu_id', $menu->id)->exists()) {
                $user->favoriteMenus()->detach($menu->id);
               
                $isFavorite = false;
            } else {
                $user->favoriteMenus()->attach($menu->id);
                $message = 'Menu added to favorites';
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
            $menu = BusinessMenu::where('uuid', $uuid)->firstOrFail();
            $isFavorite = auth()->user()->favoriteMenus()
                                      ->where('business_menu_id', $menu->id)
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
