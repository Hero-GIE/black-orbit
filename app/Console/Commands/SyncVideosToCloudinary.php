<?php

namespace App\Console\Commands;

use App\Services\CloudinaryService;
use App\Services\FirestoreServiceForVideos;   // ← renamed
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SyncVideosToCloudinary extends Command
{
    protected $signature = 'videos:sync
                            {--source= : Directory containing videos (absolute path)}
                            {--courseid= : Course ID for all videos}
                            {--pattern=*.{mp4,mov,avi,webm} : File glob pattern}
                            {--dry-run : Show what would be uploaded without doing it}';

    protected $description = 'Bulk upload videos to Cloudinary and store their URLs in Firestore';

    public function handle(CloudinaryService $cloudinary, FirestoreServiceForVideos $firestore): int
    {
        $source   = $this->option('source');
        $courseId = $this->option('courseid');
        $pattern  = $this->option('pattern') ?: '*.{mp4,mov,avi,webm}';
        $dry      = (bool) $this->option('dry-run');

        if (!$source || !is_dir($source)) {
            $this->error('Please provide a valid --source directory.');
            return self::FAILURE;
        }
        if (!$courseId) {
            $this->error('Please provide --courseid.');
            return self::FAILURE;
        }

        $files = collect(File::glob(rtrim($source, '/') . '/' . $pattern))
            ->filter(fn($f) => is_file($f))
            ->values();

        if ($files->isEmpty()) {
            $this->warn('No files matched the pattern in ' . $source);
            return self::SUCCESS;
        }

        $this->info("Found {$files->count()} video(s) in {$source}");
        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($files as $path) {
            $filename = pathinfo($path, PATHINFO_FILENAME);
            $lessonId = Str::slug($filename);

            $publicId = "videos/{$courseId}/{$lessonId}";
            $docId    = "{$courseId}_{$lessonId}";

            if ($dry) {
                $this->newLine();
                $this->line("[DRY] Would upload {$path} → {$publicId}");
                $bar->advance();
                continue;
            }

            try {
                $cloud = $cloudinary->uploadVideo($path, [
                    'folder'    => "videos/{$courseId}",
                    'public_id' => $publicId,
                    'tags'      => ["course:{$courseId}", "lesson:{$lessonId}"],
                ]);

                $firestore->createVideo($docId, [
                    'courseid'      => $courseId,
                    'lessonid'      => $lessonId,
                    'title'         => $filename,
                    'description'   => '',
                    'videourl'      => $cloud['url'],
                    'prerequisites' => [],
                    'resources'     => [],
                    'createdAt'     => now()->toISOString(),
                    'updatedAt'     => now()->toISOString(),
                    'cloudinary_public_id' => $cloud['public_id'],
                ]);

                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed: {$filename} — " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Success: {$success}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
