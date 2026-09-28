<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected Cloudinary $cloudinary;
    protected string $folder;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key'    => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => ['secure' => true],
        ]);

        $this->folder = config('services.cloudinary.folder', 'videos');
    }


    public function uploadVideo($source, array $options = []): array
    {
        $uploadApi = new UploadApi();

        // Build the Cloudinary options
        $cloudOptions = [
            'resource_type' => 'video',
            'folder'        => $options['folder'] ?? $this->folder,
            'overwrite'     => true,
            'invalidate'    => true,
            'chunk_size'    => 6000000,
        ];

        if (!empty($options['public_id'])) {
            $cloudOptions['public_id'] = $options['public_id'];
        }
        if (!empty($options['tags'])) {
            $cloudOptions['tags'] = $options['tags'];
        }
        if (!empty($options['context'])) {
            $cloudOptions['context'] = $options['context'];
        }

        // Determine the input source
        if ($source instanceof UploadedFile) {
            $cloudOptions['file'] = $source->getRealPath();
        } elseif (is_string($source)) {
            // Local absolute path or URL — Cloudinary handles both
            $cloudOptions['file'] = $source;
        } else {
            throw new \InvalidArgumentException('Unsupported source for Cloudinary upload');
        }

        $result = $uploadApi->upload($cloudOptions['file'], $cloudOptions);

        Log::info('Cloudinary video uploaded', [
            'public_id' => $result['public_id'] ?? null,
            'url'       => $result['secure_url'] ?? null,
            'bytes'     => $result['bytes'] ?? null,
        ]);

        return [
            'url'       => $result['secure_url'],
            'public_id' => $result['public_id'],
            'duration'  => $result['duration'] ?? null,
            'format'    => $result['format'] ?? null,
            'bytes'     => $result['bytes'] ?? 0,
        ];
    }

    /**
     * Delete a video from Cloudinary by public_id.
     */
    public function deleteVideo(string $publicId): bool
    {
        try {
            $uploadApi = new UploadApi();
            $uploadApi->destroy($publicId, ['resource_type' => 'video', 'invalidate' => true]);
            Log::info('Cloudinary video deleted', ['public_id' => $publicId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Cloudinary delete failed', ['public_id' => $publicId, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
