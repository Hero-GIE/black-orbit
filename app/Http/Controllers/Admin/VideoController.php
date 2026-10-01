<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VideoUploadRequest;
use App\Services\ActivityLogger;
use App\Services\CloudinaryService;
use App\Services\FirestoreServiceForVideos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoController extends Controller
{
    protected CloudinaryService $cloudinary;
    protected FirestoreServiceForVideos $firestore;

    public function __construct(CloudinaryService $cloudinary, FirestoreServiceForVideos $firestore)
    {
        $this->cloudinary = $cloudinary;
        $this->firestore  = $firestore;
    }

    public function index()
    {
        return view('admin.videos.index');
    }

    /**
     * Upload / Update.
     * Source of truth: courses/{courseId}/lessons/{docId}
     * Mirror:          videos/{docId}
     * Same doc ID in both places.
     */
    public function upload(VideoUploadRequest $request)
    {
        Log::info('[video:upload] START', [
            'courseid'    => $request->input('courseid'),
            'lessonid'    => $request->input('lessonid'),
            'title'       => $request->input('title'),
            'has_file'    => $request->hasFile('video'),
            'videoId'     => $request->input('videoId'),
            'lessonDocId' => $request->input('lessonDocId'),
        ]);

        try {
            $courseId    = $request->input('courseid');
            $lessonId    = $request->input('lessonid');
            $lessonDocId = $request->input('lessonDocId');

            $isNew = false;
            $docId = $lessonDocId ?: $request->input('videoId');

            if (!$docId) {
                $docId = $this->firestore->findLessonDocIdByField($courseId, $lessonId);
            }
            if (!$docId) {
                $docId = $this->generateFirestoreId();
                $isNew = true;
            }

            $file         = $request->file('video');
            $urlFromInput = $request->input('videourl');

            if ($isNew && !$file && !$urlFromInput) {
                return response()->json([
                    'success' => false,
                    'message' => 'Video file or URL is required',
                ], 422);
            }

            $payload = [
                'courseid'      => $courseId,
                'lessonid'      => $lessonId,
                'title'         => $request->input('title'),
                'description'   => $request->input('description', ''),
                'prerequisites' => $request->input('prerequisites', []),
                'resources'     => $request->input('resources', []),
                'updatedAt'     => now()->toISOString(),
            ];

            if ($file) {
                $publicId = "{$lessonId}_" . time();
                Log::info('[video:upload] uploading to Cloudinary', ['public_id' => $publicId]);

                $cloud = $this->cloudinary->uploadVideo($file, [
                    'folder'    => "videos/{$courseId}",
                    'public_id' => $publicId,
                    'tags'      => ["course:{$courseId}", "lesson:{$lessonId}"],
                    'context'   => [
                        'title'    => $request->input('title'),
                        'lessonid' => $lessonId,
                        'courseid' => $courseId,
                    ],
                ]);

                $payload['videourl']           = $cloud['url'];
                $payload['cloudinaryPublicId'] = $cloud['public_id'];
                $payload['duration']           = $cloud['duration'];
                $payload['bytes']              = $cloud['bytes'];
            } elseif ($urlFromInput) {
                $payload['videourl'] = $urlFromInput;
            }

            if ($isNew) {
                $payload['createdAt'] = now()->toISOString();
            } else {
                $existing = $this->firestore->getLesson($courseId, $docId)
                         ?: $this->firestore->getVideo($docId);
                $payload['createdAt'] = $existing['createdAt'] ?? now()->toISOString();
            }

            // ── WRITE 1 (source of truth) ──
            Log::info('[video:upload] writing lessons/{docId}', ['courseid' => $courseId, 'docId' => $docId]);
            $lessonWritten = true; $lessonError = null;
            try {
                $this->firestore->upsertLesson($courseId, $docId, $payload);
            } catch (\Throwable $e) {
                $lessonWritten = false;
                $lessonError   = $e->getMessage();
                Log::error('[video:upload] lesson write FAILED', ['courseid' => $courseId, 'docId' => $docId, 'error' => $e->getMessage()]);
            }

            // ── WRITE 2 (mirror) ──
            Log::info('[video:upload] writing videos/{docId}', ['docId' => $docId]);
            $videoWritten = true; $videoError = null;
            try {
                $this->firestore->createVideo($docId, $payload);
            } catch (\Throwable $e) {
                $videoWritten = false;
                $videoError   = $e->getMessage();
                Log::error('[video:upload] video write FAILED', ['docId' => $docId, 'error' => $e->getMessage()]);
            }

            if (!$lessonWritten) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson write failed: ' . $lessonError,
                ], 500);
            }

            ActivityLogger::log(
                'admin_action',
                ($isNew ? 'Video uploaded: ' : 'Video updated: ') . $request->input('title'),
                'Action by ' . session('firebase_username', 'Admin'),
                ['courseid' => $courseId, 'docId' => $docId]
            );

            return response()->json([
                'success' => true,
                'message' => $isNew ? 'Video uploaded successfully' : 'Video updated successfully',
                'data' => [
                    'id'          => $docId,
                    'lessonDocId' => $docId,
                    'courseid'    => $courseId,
                    'lessonid'    => $lessonId,
                    'videourl'    => $payload['videourl'] ?? null,
                    'writes' => [
                        'lessons'       => $lessonWritten,
                        'videos'        => $videoWritten,
                        'lessons_error' => $lessonError,
                        'videos_error'  => $videoError,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:upload] FAILED', [
                'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * List all lessons for a course. Primary read — the admin grid uses this.
     * Sorted by module number parsed from the title.
     */
    public function fetchVideos(Request $request)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $search    = trim((string) $request->query('q', ''));
            $courseid  = trim((string) $request->query('courseid', ''));
            $lessonid  = trim((string) $request->query('lessonid', ''));

            $bearer = $this->getBearer();
            if (!$bearer) {
                return response()->json(['success' => false, 'message' => 'Not authenticated', 'count' => 0], 401);
            }

            // Default course if none specified — your single course
            if (!$courseid) {
                $courseid = 'uyCGRCEBqWL31zgQNQZQ';
            }

            // Read directly from courses/{courseId}/lessons
            $lessons = $this->readAllLessons($projectId, $bearer, $courseid);

            // Apply filters
            $videos = [];
            foreach ($lessons as $lesson) {
              if ($lessonid) {
    $label = $this->lessonLabelFromTitle($lesson['title'] ?? '');
    if ($lesson['lessonid'] !== $lessonid && $label !== $lessonid) continue;
    }
                if ($search) {
                    $q = mb_strtolower($search);
                    if (!str_contains(mb_strtolower($lesson['title']), $q)
                        && !str_contains(mb_strtolower($lesson['courseid']), $q)
                        && !str_contains(mb_strtolower($lesson['lessonid']), $q)) continue;
                }
                $videos[] = $lesson;
            }

            // Sort by module number extracted from the title
            usort($videos, function ($a, $b) {
                return $this->extractTitleOrder($a['title'] ?? '') <=> $this->extractTitleOrder($b['title'] ?? '');
            });

            return response()->json([
                'success' => true,
                'message' => 'Videos retrieved successfully',
                'count'   => count($videos),
                'total'   => count($lessons),
                'data'    => $videos,
                'nextPageToken' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:fetchVideos] FAILED', [
                'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'count' => 0], 500);
        }
    }

    /**
     * Get a single lesson by ID. Tries lessons first, falls back to videos mirror.
     */
    public function getVideo(string $id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $bearer    = $this->getBearer();
            if (!$bearer) {
                return response()->json(['success' => false, 'message' => 'Not authenticated', 'count' => 0], 401);
            }

            $courseId = 'uyCGRCEBqWL31zgQNQZQ';
            $lesson = $this->firestore->getLesson($courseId, $id);

            if (!$lesson) {
                return response()->json(['success' => false, 'message' => 'Not found', 'count' => 0], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Video retrieved successfully',
                'count'   => 1,
                'data' => [
                    'id'            => $id,
                    'lessonDocId'   => $id,
                    'courseid'      => $lesson['courseid'] ?? $courseId,
                    'lessonid'      => $lesson['lessonid'] ?? '',
                    'title'         => $lesson['title'] ?? '',
                    'description'   => $lesson['description'] ?? '',
                    'videourl'      => $lesson['videourl'] ?? '',
                    'prerequisites' => $lesson['prerequisites'] ?? [],
                    'resources'     => $lesson['resources'] ?? [],
                    'createdAt'     => $lesson['createdAt'] ?? '',
                    'updatedAt'     => $lesson['updatedAt'] ?? '',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:getVideo] FAILED', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'count' => 0], 500);
        }
    }

    /**
     * Replace the video file. Updates lesson first, then video mirror.
     */
    public function replaceVideo(Request $request, string $id)
    {
        Log::info('[video:replace] START', ['id' => $id, 'has_file' => $request->hasFile('video')]);

        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:' . config('services.videos.max_size_kb', 512000),
        ]);

        try {
            $videoDoc = $this->firestore->getVideo($id);
            if (!$videoDoc) return response()->json(['success' => false, 'message' => 'Video not found'], 404);

            $courseId = $videoDoc['courseid'];
            $lessonId = $videoDoc['lessonid'];

            $cloud = $this->cloudinary->uploadVideo($request->file('video'), [
                'folder' => "videos/{$courseId}",
                'tags'   => ["course:{$courseId}", "lesson:{$lessonId}"],
            ]);

            $lessonOk = true; $lessonErr = null;
            try {
                $this->firestore->updateLessonVideoUrl($courseId, $id, $cloud['url']);
            } catch (\Throwable $e) {
                $lessonOk = false; $lessonErr = $e->getMessage();
                Log::warning('[video:replace] lesson update failed', ['id' => $id, 'error' => $e->getMessage()]);
            }

            $videoOk = true; $videoErr = null;
            try {
                $this->firestore->updateVideoUrl($id, $cloud['url']);
            } catch (\Throwable $e) {
                $videoOk = false; $videoErr = $e->getMessage();
                Log::warning('[video:replace] video mirror update failed', ['id' => $id, 'error' => $e->getMessage()]);
            }

            ActivityLogger::log(
                'admin_action', 'Video replaced',
                'Video ' . $id . ' replaced by ' . session('firebase_username', 'Admin'),
                ['courseid' => $courseId, 'docId' => $id]
            );

            return response()->json([
                'success' => true,
                'message' => 'Video replaced successfully',
                'data' => [
                    'id'          => $id,
                    'lessonDocId' => $id,
                    'courseid'    => $courseId,
                    'lessonid'    => $lessonId,
                    'videourl'    => $cloud['url'],
                    'writes' => [
                        'lessons'       => $lessonOk,
                        'videos'        => $videoOk,
                        'lessons_error' => $lessonErr,
                        'videos_error'  => $videoErr,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:replace] FAILED', [
                'id' => $id, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete lesson first, then video mirror.
     */
    public function destroy(Request $request, string $id)
    {
        try {
            $courseId = 'uyCGRCEBqWL31zgQNQZQ';

            $lessonDeleted = false; $lessonError = null;
            try {
                $lessonDeleted = $this->firestore->deleteLesson($courseId, $id);
            } catch (\Throwable $e) {
                $lessonError = $e->getMessage();
                Log::warning('[video:destroy] lesson delete failed', ['courseid' => $courseId, 'docId' => $id, 'error' => $e->getMessage()]);
            }

            $projectId = env('FIREBASE_PROJECT_ID');
            $bearer    = $this->getBearer();
            $videoDeleted = false;
            if ($bearer) {
                $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos/{$id}";
                $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])->timeout(30)->delete($url);
                $videoDeleted = $resp->successful() || $resp->status() === 404;
            }

            ActivityLogger::log(
                'admin_action', 'Video deleted',
                'Video ' . $id . ' deleted by ' . session('firebase_username', 'Admin'),
                ['courseid' => $courseId, 'docId' => $id]
            );

            return response()->json([
                'success' => true,
                'message' => 'Video deleted',
                'count'   => 1,
                'data' => [
                    'id' => $id, 'courseid' => $courseId,
                    'writes' => [
                        'lessons'       => $lessonDeleted,
                        'videos'        => $videoDeleted,
                        'lessons_error' => $lessonError,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:destroy] FAILED', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'count' => 0], 500);
        }
    }


  /**
 * Distinct list of courses — reads from courses/ collection.
 */
public function fetchCourses()
{
    try {
        $projectId = env('FIREBASE_PROJECT_ID');
        $bearer    = $this->getBearer();
        if (!$bearer) {
            return response()->json(['success' => false, 'message' => 'Not authenticated', 'count' => 0], 401);
        }

        $baseUrl   = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses";
        $courses   = [];
        $pageToken = null;

        for ($page = 0; $page < 50; $page++) {
            $query = ['pageSize' => 300];
            if ($pageToken) $query['pageToken'] = $pageToken;

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                ->timeout(30)->get($baseUrl . '?' . http_build_query($query));
            if (!$response->successful()) break;

            $data = $response->json();
            foreach ($data['documents'] ?? [] as $doc) {
                $courseId = $doc['fields']['courseid']['stringValue'] ?? '';
                if ($courseId === '') $courseId = basename($doc['name']);
                if (!in_array($courseId, $courses, true)) $courses[] = $courseId;
            }
            $pageToken = $data['nextPageToken'] ?? null;
            if (!$pageToken) break;
        }

        sort($courses, SORT_NATURAL | SORT_FLAG_CASE);

        return response()->json([
            'success' => true,
            'message' => 'Courses retrieved',
            'count'   => count($courses),
            'data'    => $courses,
        ]);
    } catch (\Throwable $e) {
        Log::error('[video:fetchCourses] FAILED', ['message' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => $e->getMessage(), 'count' => 0], 500);
    }
}

    /**
     * Distinct list of lesson IDs.
     */
/**
 * Distinct list of lessons across all courses.
 * Returns labels like "lesson_00", "lesson_01", ..., "lesson_14"
 * derived from the module number in each lesson's title.
 */
public function fetchLessons()
{
    try {
        $projectId = env('FIREBASE_PROJECT_ID');
        $bearer    = $this->getBearer();
        if (!$bearer) {
            return response()->json(['success' => false, 'message' => 'Not authenticated', 'count' => 0], 401);
        }

        // 1. Get all courses
        $courses = [];
        $coursesUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses";
        $pageToken = null;
        for ($page = 0; $page < 50; $page++) {
            $query = ['pageSize' => 300];
            if ($pageToken) $query['pageToken'] = $pageToken;
            $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                ->timeout(30)->get($coursesUrl . '?' . http_build_query($query));
            if (!$resp->successful()) break;
            $data = $resp->json();
            foreach ($data['documents'] ?? [] as $doc) {
                $courses[] = basename($doc['name']);
            }
            $pageToken = $data['nextPageToken'] ?? null;
            if (!$pageToken) break;
        }

        // 2. Walk each course, pull lessons, build labels
        $lessons = [];
        foreach ($courses as $courseId) {
            $lessonsUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$courseId}/lessons";
            $pageToken = null;
            for ($page = 0; $page < 50; $page++) {
                $query = ['pageSize' => 300];
                if ($pageToken) $query['pageToken'] = $pageToken;
                $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                    ->timeout(30)->get($lessonsUrl . '?' . http_build_query($query));
                if (!$resp->successful()) break;
                $data = $resp->json();
                foreach ($data['documents'] ?? [] as $doc) {
                    $f = $doc['fields'] ?? [];
                    $title = $f['title']['stringValue'] ?? '';
                    $label = $this->lessonLabelFromTitle($title);
                    if ($label !== '' && !in_array($label, $lessons, true)) {
                        $lessons[] = $label;
                    }
                }
                $pageToken = $data['nextPageToken'] ?? null;
                if (!$pageToken) break;
            }
        }

        // 3. Sort naturally so lesson_00 → lesson_01 → ... → lesson_14
        sort($lessons, SORT_NATURAL | SORT_FLAG_CASE);

        return response()->json([
            'success' => true,
            'message' => 'Lessons retrieved',
            'count'   => count($lessons),
            'data'    => $lessons,
        ]);
    } catch (\Throwable $e) {
        Log::error('[video:fetchLessons] FAILED', ['message' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => $e->getMessage(), 'count' => 0], 500);
    }
}

    /**
     * Read every lesson doc in a course.
     */
    private function readAllLessons(string $projectId, string $bearer, string $courseId): array
    {
        $baseUrl   = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$courseId}/lessons";
        $out       = [];
        $pageToken = null;

        do {
            $q = ['pageSize' => 300];
            if ($pageToken) $q['pageToken'] = $pageToken;

            $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                ->timeout(60)->get($baseUrl . '?' . http_build_query($q));
            if (!$resp->successful()) break;

            $data = $resp->json();
            foreach ($data['documents'] ?? [] as $doc) {
                $f = $doc['fields'] ?? [];
                $out[] = [
                    'id'            => basename($doc['name']),
                    'lessonDocId'   => basename($doc['name']),
                    'courseid'      => $f['courseid']['stringValue'] ?? $courseId,
                    'lessonid'      => $f['lessonid']['stringValue'] ?? '',
                    'title'         => $f['title']['stringValue'] ?? '',
                    'description'   => $f['description']['stringValue'] ?? '',
                    'videourl'      => $f['videourl']['stringValue'] ?? '',
                    'prerequisites' => $this->pluckStringArray($f['prerequisites'] ?? null),
                    'resources'     => $this->pluckStringArray($f['resources'] ?? null),
                    'createdAt'     => $f['createdAt']['timestampValue'] ?? '',
                    'updatedAt'     => $f['updatedAt']['timestampValue'] ?? '',
                ];
            }
            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken);

        return $out;
    }

    /**
     * Read all videos from the top-level mirror (used when no course filter is set).
     */
    private function fetchFromVideosMirror(string $projectId, string $bearer, string $search, string $lessonid): \Illuminate\Http\JsonResponse
    {
        $baseUrl   = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos";
        $videos    = [];
        $pageToken = null;

        for ($page = 0; $page < 30; $page++) {
            $query = ['pageSize' => 300];
            if ($pageToken) $query['pageToken'] = $pageToken;

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                ->timeout(30)->get($baseUrl . '?' . http_build_query($query));
            if (!$response->successful()) break;

            $data = $response->json();
            foreach ($data['documents'] ?? [] as $doc) {
                $f = $doc['fields'] ?? [];
                $video = [
                    'id'            => basename($doc['name']),
                    'lessonDocId'   => basename($doc['name']),
                    'courseid'      => $f['courseid']['stringValue'] ?? '',
                    'lessonid'      => $f['lessonid']['stringValue'] ?? '',
                    'title'         => $f['title']['stringValue'] ?? '',
                    'description'   => $f['description']['stringValue'] ?? '',
                    'videourl'      => $f['videourl']['stringValue'] ?? '',
                    'prerequisites' => $this->pluckStringArray($f['prerequisites'] ?? null),
                    'resources'     => $this->pluckStringArray($f['resources'] ?? null),
                    'createdAt'     => $f['createdAt']['timestampValue'] ?? '',
                    'updatedAt'     => $f['updatedAt']['timestampValue'] ?? '',
                ];

                if ($lessonid && $video['lessonid'] !== $lessonid) continue;
                if ($search) {
                    $q = mb_strtolower($search);
                    if (!str_contains(mb_strtolower($video['title']), $q)
                        && !str_contains(mb_strtolower($video['courseid']), $q)
                        && !str_contains(mb_strtolower($video['lessonid']), $q)) continue;
                }
                $videos[] = $video;
            }
            $pageToken = $data['nextPageToken'] ?? null;
            if (!$pageToken) break;
        }

        usort($videos, function ($a, $b) {
            return $this->extractTitleOrder($a['title'] ?? '') <=> $this->extractTitleOrder($b['title'] ?? '');
        });

        return response()->json([
            'success' => true,
            'message' => 'Videos retrieved successfully',
            'count'   => count($videos),
            'total'   => count($videos),
            'data'    => $videos,
            'nextPageToken' => null,
        ]);
    }

    private function extractTitleOrder(string $title): int
    {
        $t = trim($title);

        if (preg_match('/^module\s+(\d+)/i', $t, $m)) {
            return (int) $m[1];
        }

        if (stripos($t, 'introduction') === 0) return 0;
        if (stripos($t, 'outro') === 0)        return 14;
        if (stripos($t, 'student') === 0)      return 13;

        return 9999;
    }

    private function lessonLabelFromTitle(string $title): string
{
    $order = $this->extractTitleOrder($title);
    if ($order >= 9999) return '';
    return 'lesson_' . str_pad((string) $order, 2, '0', STR_PAD_LEFT);
}

    private function getBearer(): ?string
    {
        $svc = app(FirestoreServiceForVideos::class);
        $ref = new \ReflectionClass($svc);
        $m   = $ref->getMethod('token');
        $m->setAccessible(true);
        return $m->invoke($svc);
    }

    private function pluckStringArray(?array $arrayValue): array
    {
        if (!$arrayValue || !isset($arrayValue['arrayValue']['values'])) return [];
        $out = [];
        foreach ($arrayValue['arrayValue']['values'] as $v) {
            if (isset($v['stringValue'])) $out[] = $v['stringValue'];
        }
        return $out;
    }

    private function generateFirestoreId(int $length = 20): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }
}
