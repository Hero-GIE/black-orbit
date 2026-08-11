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
        // Generate unique filename
        $filename = time() . '_' . Str::random(16) . '.' . $file->getClientOriginalExtension();
        $path = "uploads/{$userId}/{$folder}";

        // Store file on server
        $storedPath = $file->storeAs($path, $filename, 'public');

        if (!$storedPath) {
            throw new \Exception('Failed to store image');
        }

        // Generate URL from your server
        $url = asset('storage/' . $storedPath);

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
        $path = "uploads/{$userId}";

        if (!Storage::disk('public')->exists($path)) {
            return ['data' => [], 'total' => 0];
        }

        $files = Storage::disk('public')->files($path);
        $images = [];

        foreach ($files as $file) {
            $images[] = [
                'path' => $file,
                'url' => asset('storage/' . $file),
                'size' => Storage::disk('public')->size($file),
                'last_modified' => date('Y-m-d H:i:s', Storage::disk('public')->lastModified($file)),
            ];
        }

        // Apply pagination
        $paginated = array_slice($images, $offset, $limit);

        return [
            'data' => $paginated,
            'total' => count($images),
        ];
    }

    public function getImageUrl(string $path): string
    {
        return asset('storage/' . $path);
    }
}
