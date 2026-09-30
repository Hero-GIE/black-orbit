<?php

namespace App\Console\Commands;

use App\Services\FirestoreServiceForVideos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ResetAndSeedLessons extends Command
{
    protected $signature = 'videos:reset-and-seed
                            {--course=uyCGRCEBqWL31zgQNQZQ : Target course ID}
                            {--dry-run : Show what would be done without touching Firestore}';

    protected $description = 'Delete existing lesson docs in a course, then write all videos from the top-level videos collection into courses/{course}/lessons/lesson_XX';

    public function handle(FirestoreServiceForVideos $firestore): int
    {
        $projectId = env('FIREBASE_PROJECT_ID');
        if (!$projectId) {
            $this->error('FIREBASE_PROJECT_ID is not set.');
            return self::FAILURE;
        }

        $courseId = $this->option('course');
        $dryRun   = (bool) $this->option('dry-run');

        $bearer = $this->getBearer($firestore);
        if (!$bearer) {
            $this->error('Could not obtain Firestore bearer token.');
            return self::FAILURE;
        }

        // -------------------------------------------------------------
        // STEP 1: List + delete existing lesson docs
        // -------------------------------------------------------------
        $this->info("STEP 1 — Deleting existing lessons in courses/{$courseId}/lessons");

        $existing = $this->listExistingLessons($projectId, $bearer, $courseId);
        $this->line("  Found " . count($existing) . " existing lesson doc(s).");

        foreach ($existing as $docId) {
            $this->line("  🗑  {$docId}");

            if ($dryRun) continue;

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}"
                 . "/databases/(default)/documents/courses/{$courseId}/lessons/{$docId}";

            $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                ->timeout(30)->delete($url);

            if (!$resp->successful() && $resp->status() !== 404) {
                $this->error("     Failed: {$resp->body()}");
            }
        }

        // -------------------------------------------------------------
        // STEP 2: Fetch all videos, sort by lessonid (lesson_00 → lesson_14)
        // -------------------------------------------------------------
        $this->newLine();
        $this->info('STEP 2 — Fetching videos from top-level collection');

        $videos = $this->fetchAllVideos($projectId, $bearer);
        $this->line("  Found " . count($videos) . " video doc(s).");

        usort($videos, fn ($a, $b) => strnatcmp($a['lessonid'] ?? '', $b['lessonid'] ?? ''));

        // -------------------------------------------------------------
        // STEP 3: Write each video into courses/{course}/lessons/lesson_XX
        // -------------------------------------------------------------
        $this->newLine();
        $this->info("STEP 3 — Writing to courses/{$courseId}/lessons/lesson_XX");

        $written = 0;
        $failed  = 0;

        foreach ($videos as $v) {

    $targetId = $this->generateFirestoreId();

    if (!$targetId) {
        $this->warn("  Skipping video with no id: {$v['title']}");
        continue;
            }

            $payload = [
                'courseid'      => $courseId,
                'lessonid'      => $targetId,
                'title'         => $v['title'] ?? '',
                'description'   => $v['description'] ?? '',
                'videourl'      => $v['videourl'] ?? '',
                'prerequisites' => $v['prerequisites'] ?? [],
                'resources'     => $v['resources'] ?? [],
                'createdAt'     => $v['createdAt'] ?? now()->toISOString(),
                'updatedAt'     => now()->toISOString(),
            ];

            $this->line("  → {$targetId}  ({$payload['title']})");

            if ($dryRun) { $written++; continue; }

            try {
                $firestore->upsertLesson($courseId, $targetId, $payload);
                $written++;
            } catch (\Throwable $e) {
                $this->error("     Failed: {$e->getMessage()}");
                $failed++;
            }
        }

        // -------------------------------------------------------------
        // Summary
        // -------------------------------------------------------------
        $this->newLine();
        $this->info("Course           : {$courseId}");
        $this->info("Deleted old      : " . count($existing));
        $this->info("Videos processed : " . count($videos));
        $this->info("Written          : {$written}");
        $this->info("Failed           : {$failed}");

        if ($dryRun) {
            $this->warn('DRY RUN — nothing was written or deleted.');
        }

        return self::SUCCESS;
    }

    private function getBearer(FirestoreServiceForVideos $svc): ?string
    {
        $r = new \ReflectionClass($svc);
        $m = $r->getMethod('token');
        $m->setAccessible(true);
        return $m->invoke($svc);
    }

    private function listExistingLessons(string $projectId, string $bearer, string $courseId): array
    {
        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}"
             . "/databases/(default)/documents/courses/{$courseId}/lessons?pageSize=300";

        $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
            ->timeout(30)->get($url);

        if (!$resp->successful()) return [];

        $ids = [];
        foreach ($resp->json()['documents'] ?? [] as $doc) {
            $ids[] = basename($doc['name']);
        }
        return $ids;
    }

    private function fetchAllVideos(string $projectId, string $bearer): array
    {
        $base = "https://firestore.googleapis.com/v1/projects/{$projectId}"
              . "/databases/(default)/documents/videos";

        $out = [];
        $pageToken = null;

        do {
            $q = ['pageSize' => 300];
            if ($pageToken) $q['pageToken'] = $pageToken;

            $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                ->timeout(60)->get($base . '?' . http_build_query($q));

            if (!$resp->successful()) break;

            $data = $resp->json();
            foreach ($data['documents'] ?? [] as $doc) {
                $f = $doc['fields'] ?? [];
                $out[] = [
                    'id'            => basename($doc['name']),
                    'courseid'      => $f['courseid']['stringValue'] ?? '',
                    'lessonid'      => $f['lessonid']['stringValue'] ?? '',
                    'title'         => $f['title']['stringValue'] ?? '',
                    'description'   => $f['description']['stringValue'] ?? '',
                    'videourl'      => $f['videourl']['stringValue'] ?? '',
                    'prerequisites' => $this->pluck($f['prerequisites'] ?? null),
                    'resources'     => $this->pluck($f['resources'] ?? null),
                    'createdAt'     => $f['createdAt']['timestampValue'] ?? null,
                    'updatedAt'     => $f['updatedAt']['timestampValue'] ?? null,
                ];
            }
            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        return $out;
    }

    private function pluck(?array $arr): array
    {
        if (!$arr || !isset($arr['arrayValue']['values'])) return [];
        $out = [];
        foreach ($arr['arrayValue']['values'] as $v) {
            if (isset($v['stringValue'])) $out[] = $v['stringValue'];
        }
        return $out;
    }

    private function generateFirestoreId(int $length = 20): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $max      = strlen($alphabet) - 1;
        $out      = '';

        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }

        return $out;
    }
}
