<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ImageService
{
  public function upload(UploadedFile $file, string $userId, string $folder = 'general'): array
{

    $filename = time() . '_' . Str::random(16) . '.' . $file->getClientOriginalExtension();
    $path = "uploads/{$userId}/{$folder}";


    $storedPath = $file->storeAs($path, $filename, 'public');

    if (!$storedPath) {
        throw new \Exception('Failed to store image');
    }

    $url = Storage::disk('public')->url($storedPath);

    Log::info('Image uploaded', ['path' => $storedPath]);

    return [
        'user_id' => $userId,
        'filename' => $filename,
        'original_name' => $file->getClientOriginalName(),
        'path' => $storedPath,
        'url' => $url,
        'folder' => $folder,
        'mime_type' => $file->getMimeType(),
        'size' => $file->getSize(),
        'created_at' => now()->toISOString(),
    ];
}

    public function delete(string $path, string $userId): bool
    {
        // Check if file exists
        if (!Storage::disk('public')->exists($path)) {
            throw new \Exception('Image not found');
        }

        // Delete physical file
        Storage::disk('public')->delete($path);
        Log::info('Image deleted', ['path' => $path]);

        return true;
    }

    public function getUserImages(string $userId, int $limit = 50, int $offset = 0): array
    {
        $basePath = "uploads/{$userId}";

        // Check if directory exists
        if (!Storage::disk('public')->exists($basePath)) {
            Log::info('User directory not found', ['path' => $basePath]);
            return ['data' => [], 'total' => 0];
        }

        $allFiles = Storage::disk('public')->allFiles($basePath);

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        $imageFiles = array_filter($allFiles, function ($file) use ($imageExtensions) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($extension, $imageExtensions);
        });

        usort($imageFiles, function ($a, $b) {
            $timeA = Storage::disk('public')->lastModified($a);
            $timeB = Storage::disk('public')->lastModified($b);
            return $timeB - $timeA;
        });

        $total = count($imageFiles);

        // Apply pagination
        $paginatedFiles = array_slice($imageFiles, $offset, $limit);

        $images = [];
        foreach ($paginatedFiles as $file) {
            // Extract folder name from path
            $folder = dirname($file);
            $folder = str_replace("uploads/{$userId}/", '', $folder);

            $images[] = [
                'path' => $file,
                'url' => asset('storage/' . $file),
                'size' => Storage::disk('public')->size($file),
                'last_modified' => date('Y-m-d H:i:s', Storage::disk('public')->lastModified($file)),
                'filename' => basename($file),
                'folder' => $folder ?: 'root',
                'user_id' => $userId,
            ];
        }

        Log::info('User images retrieved', [
            'user_id' => $userId,
            'total' => $total,
            'returned' => count($images)
        ]);

        return [
            'data' => $images,
            'total' => $total,
        ];
    }

    public function getImageUrl(string $path): string
    {
        return asset('storage/' . $path);
    }
}
