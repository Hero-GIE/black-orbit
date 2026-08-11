<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class ImageHelper
{
    /**
     * Get public image URL
     */
    public static function getPublicUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    /**
     * Generate image path for user
     */
    public static function getUserImagePath(string $userId, string $folder = 'general'): string
    {
        return "uploads/{$userId}/{$folder}";
    }

    /**
     * Delete directory and contents
     */
    public static function deleteUserDirectory(string $userId): bool
    {
        $path = "uploads/{$userId}";
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->deleteDirectory($path);
        }
        return false;
    }
}
