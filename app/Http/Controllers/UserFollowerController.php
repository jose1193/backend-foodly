<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserFollowerController extends Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            
            return response()->json([
                'followers' => $user->followers->pluck('uuid'),
                'following' => $user->following->pluck('uuid'),
                'followers_count' => $user->followers->count(),
                'following_count' => $user->following->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving followers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggle(Request $request, $userUuid)
    {
        try {
            $userToFollow = User::where('uuid', $userUuid)->firstOrFail();
            $user = auth()->user();

            if ($user->id === $userToFollow->id) {
                return response()->json([
                    'message' => 'You cannot follow yourself'
                ], 400);
            }

            if ($user->following()->where('following_id', $userToFollow->id)->exists()) {
                $user->following()->detach($userToFollow->id);
                $message = 'User unfollowed successfully';
                $isFollowing = false;
            } else {
                $user->following()->attach($userToFollow->id);
                $message = 'User followed successfully';
                $isFollowing = true;
            }

            return response()->json([
               
                'is_following' => $isFollowing
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error processing follow request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function check($userUuid)
    {
        try {
            $userToCheck = User::where('uuid', $userUuid)->firstOrFail();
            $isFollowing = auth()->user()->following()
                                       ->where('following_id', $userToCheck->id)
                                       ->exists();

            return response()->json(['is_following' => $isFollowing]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error checking follow status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}