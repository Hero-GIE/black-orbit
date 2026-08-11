<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ImageController extends Controller
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:' . env('IMAGE_MAX_SIZE', 10240),
                'folder' => 'sometimes|string|max:100',
            ]);

            $file = $request->file('image');
            $userId = $request->input('firebase_user.uid', 'test_user');
            $folder = $request->input('folder', 'general');

            $result = $this->imageService->upload($file, $userId, $folder);

            ActivityLogger::log('admin_action', 'Image uploaded', 'Uploaded to ' . ($result['path'] ?? 'unknown'), ['folder' => $folder]);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Image uploaded successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadMultiple(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'images' => 'required|array|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:' . env('IMAGE_MAX_SIZE', 10240),
                'folder' => 'sometimes|string|max:100',
            ]);

            $userId = $request->input('firebase_user.uid', 'test_user');
            $folder = $request->input('folder', 'general');
            $files = $request->file('images');

            $results = [];
            foreach ($files as $file) {
                $results[] = $this->imageService->upload($file, $userId, $folder);
            }

            ActivityLogger::log('admin_action', 'Images uploaded', count($results) . ' images uploaded', ['folder' => $folder]);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => count($results) . ' images uploaded successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Upload failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'path' => 'required|string',
            ]);

            $userId = $request->input('firebase_user.uid', 'test_user');
            $path = $request->input('path');

            if (!str_starts_with($path, "uploads/{$userId}/")) {
                return response()->json([
                    'success' => false,
                    'error' => 'Forbidden',
                    'message' => 'You can only delete your own files'
                ], 403);
            }

            $this->imageService->delete($path, $userId);

            ActivityLogger::log('admin_action', 'Image deleted', 'Deleted ' . $path);

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Delete failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $userId = $request->input('firebase_user.uid', 'test_user');
            $limit = $request->input('limit', 50);
            $offset = $request->input('offset', 0);

            $result = $this->imageService->getUserImages($userId, $limit, $offset);

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'meta' => [
                    'total' => $result['total'],
                    'limit' => (int)$limit,
                    'offset' => (int)$offset,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to list images',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getUrl(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'path' => 'required|string',
            ]);

            $url = $this->imageService->getImageUrl($request->input('path'));

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $url,
                    'path' => $request->input('path'),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get URL',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
