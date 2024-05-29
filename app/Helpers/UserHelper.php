<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;


class UserHelper
{
    /**
     * Get the URL to the user's profile photo.
     *
     * @param $user
     * @return string
     */

    /**
     * Generate an avatar URL with the initial letter of the user's name.
     *
     * @param $name
     * @return string
     */
    public static function generateAvatarUrl($name)
    {
        $initials = strtoupper(substr($name, 0, 1));
        return 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&color=7F9CF5&background=EBF4FF';
    }
}