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
use Illuminate\Support\Str;

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
     * Upload / Update — writes to BOTH videos/{id} AND courses/{c}/lessons/{l}
     */
    public function upload(VideoUploadRequest $request)
    {
        Log::info('[video:upload] START', [
            'courseid' => $request->input('courseid'),
            'lessonid' => $request->input('lessonid'),
            'title'    => $request->input('title'),
            'has_file' => $request->hasFile('video'),
            'video_id' => $request->input('videoId'),
        ]);

        try {
            $docId = $request->input('videoId');
            $isUpdating = !empty($docId);
            if (!$isUpdating) {
                $docId = Str::uuid()->toString();
            }

            $file = $request->file('video');
            $urlFromInput = $request->input('videourl');
            if (!$isUpdating && !$file && !$urlFromInput) {
                return response()->json(['success' => false, 'message' => 'Video file or URL is required'], 422);
            }

            $courseId = $request->input('courseid');
            $lessonId = $request->input('lessonid');

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
                $payload['videourl']           = $urlFromInput;
                $payload['cloudinaryPublicId'] = null;
                $payload['duration']           = null;
                $payload['bytes']              = null;
            }

            if (!$isUpdating) {
                $payload['createdAt'] = now()->toISOString();
            }

            // -------- WRITE 1: videos/{docId} --------
            Log::info('[video:upload] writing to videos', ['docId' => $docId]);
            $this->firestore->createVideo($docId, $payload);

            // -------- WRITE 2: courses/{courseId}/lessons/{lessonDocId} --------
            $lessonDocId = $request->input('lessonDocId');
            if (!$lessonDocId) {
                $lessonDocId = $this->firestore->findLessonDocIdByField($courseId, $lessonId);
            }
            if (!$lessonDocId) {
                $lessonDocId = $this->generateFirestoreId();
            }

            $lessonPayload = $payload;
            $lessonPayload['lessonid'] = $lessonDocId;
            $lessonPayload['courseid'] = $courseId;

            $existing = $this->firestore->getLesson($courseId, $lessonDocId);
            if ($existing && !empty($existing['createdAt'])) {
                $lessonPayload['createdAt'] = $existing['createdAt'];
            }

            $subWritten = true;
            $subError = null;
            try {
                $this->firestore->upsertLesson($courseId, $lessonDocId, $lessonPayload);
                Log::info('[video:upload] lesson write OK', [
                    'courseid' => $courseId, 'lessonDocId' => $lessonDocId,
                ]);
            } catch (\Throwable $e) {
                $subWritten = false;
                $subError = $e->getMessage();
                Log::warning('[video:upload] lesson write FAILED', [
                    'courseid' => $courseId, 'lessonDocId' => $lessonDocId, 'error' => $e->getMessage(),
                ]);
            }

            ActivityLogger::log(
                'admin_action',
                ($isUpdating ? 'Video updated: ' : 'Video uploaded: ') . $request->input('title'),
                'Action by ' . session('firebase_username', 'Admin'),
                ['docId' => $docId, 'lessonDocId' => $lessonDocId]
            );

            return response()->json([
                'success' => true,
                'message' => $isUpdating ? 'Video updated successfully' : 'Video uploaded successfully',
                'data' => [
                    'id'          => $docId,
                    'courseid'    => $courseId,
                    'lessonid'    => $lessonId,
                    'lessonDocId' => $lessonDocId,
                    'videourl'    => $payload['videourl'] ?? null,
                    'writes' => [
                        'videos'        => true,
                        'lessons'       => $subWritten,
                        'lessons_error' => $subError,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:upload] FAILED', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Replace Video File — updates videourl on both docs
     */
    public function replaceVideo(Request $request, string $id)
    {
        Log::info('[video:replace] START', ['id' => $id, 'has_file' => $request->hasFile('video')]);

        $request->validate([
            'video'       => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:' . config('services.videos.max_size_kb', 512000),
            'courseid'    => 'required|string|max:100',
            'lessonid'    => 'required|string|max:100',
            'lessonDocId' => 'nullable|string|max:100',
        ]);

        try {
            $courseId    = $request->input('courseid');
            $lessonId    = $request->input('lessonid');
            $lessonDocId = $request->input('lessonDocId');

            if (!$lessonDocId) {
                $lessonDocId = $this->firestore->findLessonDocIdByField($courseId, $lessonId);
            }
            if (!$lessonDocId) {
                return response()->json(['success' => false, 'message' => 'Lesson doc not found'], 404);
            }

            $cloud = $this->cloudinary->uploadVideo($request->file('video'), [
                'folder' => "videos/{$courseId}",
                'tags'   => ["course:{$courseId}", "lesson:{$lessonDocId}"],
            ]);

            $this->firestore->updateVideoUrl($id, $cloud['url']);

            $subWritten = true;
            $subError = null;
            try {
                $this->firestore->updateLessonVideoUrl($courseId, $lessonDocId, $cloud['url']);
            } catch (\Throwable $e) {
                $subWritten = false;
                $subError = $e->getMessage();
                Log::warning('[video:replace] lesson update FAILED', [
                    'courseid' => $courseId, 'lessonDocId' => $lessonDocId, 'error' => $e->getMessage(),
                ]);
            }

            ActivityLogger::log(
                'admin_action',
                'Video replaced',
                'Video ' . $id . ' replaced by ' . session('firebase_username', 'Admin'),
                ['courseid' => $courseId, 'lessonDocId' => $lessonDocId]
            );

            return response()->json([
                'success' => true,
                'message' => 'Video replaced successfully',
                'data' => [
                    'id'          => $id,
                    'courseid'    => $courseId,
                    'lessonid'    => $lessonId,
                    'lessonDocId' => $lessonDocId,
                    'videourl'    => $cloud['url'],
                    'writes' => [
                        'videos'        => true,
                        'lessons'       => $subWritten,
                        'lessons_error' => $subError,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:replace] FAILED', [
                'id' => $id, 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function fetchVideos(Request $request)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');

            $limit    = min((int) $request->query('limit', 24), 100);
            $search   = trim((string) $request->query('q', ''));
            $courseid = trim((string) $request->query('courseid', ''));
            $lessonid = trim((string) $request->query('lessonid', ''));

            $authToken = app(FirestoreServiceForVideos::class);
            $reflection = new \ReflectionClass($authToken);
            $method = $reflection->getMethod('token');
            $method->setAccessible(true);
            $bearer = $method->invoke($authToken);

            if (!$bearer) {
                return response()->json(['success' => false, 'message' => 'Not authenticated', 'count' => 0], 401);
            }

            $baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos";
            $videos = [];
            $totalFetched = 0;
            $pageToken = null;

            for ($page = 0; $page < 30; $page++) {
                $query = ['pageSize' => 300];
                if ($pageToken) $query['pageToken'] = $pageToken;

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                    ->timeout(30)
                    ->get($baseUrl . '?' . http_build_query($query));

                if (!$response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Firestore auth failed (HTTP ' . $response->status() . ')',
                        'count'   => count($videos),
                    ], 500);
                }

                $data = $response->json();
                foreach ($data['documents'] ?? [] as $doc) {
                    $totalFetched++;
                    $f = $doc['fields'] ?? [];
                    $video = [
                        'id'            => basename($doc['name']),
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

                    if ($courseid && $video['courseid'] !== $courseid) continue;
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
                $courseCmp = strnatcmp($a['courseid'] ?? '', $b['courseid'] ?? '');
                if ($courseCmp !== 0) return $courseCmp;
                return strnatcmp($a['lessonid'] ?? '', $b['lessonid'] ?? '');
            });

            return response()->json([
                'success' => true,
                'message' => 'Videos retrieved successfully',
                'count'   => count($videos),
                'total'   => $totalFetched,
                'filters' => [
                    'q'        => $search,
                    'courseid' => $courseid,
                    'lessonid' => $lessonid,
                    'limit'    => $limit,
                ],
                'data' => $videos,
                'nextPageToken' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:fetchVideos] FAILED', [
                'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'count' => 0], 500);
        }
    }

    public function fetchCourses()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');

            $authToken = app(FirestoreServiceForVideos::class);
            $reflection = new \ReflectionClass($authToken);
            $method = $reflection->getMethod('token');
            $method->setAccessible(true);
            $bearer = $method->invoke($authToken);

            if (!$bearer) {
                return response()->json(['success' => false, 'message' => 'Not authenticated', 'count' => 0], 401);
            }

            $baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos";
            $courses = [];
            $pageToken = null;

            for ($page = 0; $page < 50; $page++) {
                $query = ['pageSize' => 300];
                if ($pageToken) $query['pageToken'] = $pageToken;

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                    ->timeout(30)
                    ->get($baseUrl . '?' . http_build_query($query));

                if (!$response->successful()) {
                    return response()->json(['success' => false, 'message' => 'Firestore auth failed (HTTP ' . $response->status() . ')', 'count' => count($courses)], 500);
                }

                $data = $response->json();
                foreach ($data['documents'] ?? [] as $doc) {
                    $c = $doc['fields']['courseid']['stringValue'] ?? '';
                    if ($c !== '' && !in_array($c, $courses, true)) $courses[] = $c;
                }
                $pageToken = $data['nextPageToken'] ?? null;
                if (!$pageToken) break;
            }

            sort($courses, SORT_NATURAL | SORT_FLAG_CASE);

            return response()->json([
                'success' => true,
                'message' => 'Courses retrieved successfully',
                'count'   => count($courses),
                'data'    => $courses,
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:fetchCourses] FAILED', [
                'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'count' => 0], 500);
        }
    }

    public function fetchLessons()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');

            $authToken = app(FirestoreServiceForVideos::class);
            $reflection = new \ReflectionClass($authToken);
            $method = $reflection->getMethod('token');
            $method->setAccessible(true);
            $bearer = $method->invoke($authToken);

            if (!$bearer) {
                return response()->json(['success' => false, 'message' => 'Not authenticated', 'count' => 0], 401);
            }

            $baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos";
            $lessons = [];
            $pageToken = null;

            for ($page = 0; $page < 50; $page++) {
                $query = ['pageSize' => 300];
                if ($pageToken) $query['pageToken'] = $pageToken;

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                    ->timeout(30)
                    ->get($baseUrl . '?' . http_build_query($query));

                if (!$response->successful()) {
                    return response()->json(['success' => false, 'message' => 'Firestore auth failed (HTTP ' . $response->status() . ')', 'count' => count($lessons)], 500);
                }

                $data = $response->json();
                foreach ($data['documents'] ?? [] as $doc) {
                    $l = $doc['fields']['lessonid']['stringValue'] ?? '';
                    if ($l !== '' && !in_array($l, $lessons, true)) $lessons[] = $l;
                }
                $pageToken = $data['nextPageToken'] ?? null;
                if (!$pageToken) break;
            }

            sort($lessons, SORT_NATURAL | SORT_FLAG_CASE);

            return response()->json([
                'success' => true,
                'message' => 'Lessons retrieved successfully',
                'count'   => count($lessons),
                'data'    => $lessons,
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:fetchLessons] FAILED', [
                'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'count' => 0], 500);
        }
    }

    public function getVideo(string $id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');

            $authToken = app(FirestoreServiceForVideos::class);
            $reflection = new \ReflectionClass($authToken);
            $method = $reflection->getMethod('token');
            $method->setAccessible(true);
            $bearer = $method->invoke($authToken);

            if (!$bearer) {
                return response()->json(['success' => false, 'message' => 'Not authenticated', 'count' => 0], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])->timeout(30)->get($url);

            if (!$response->successful()) {
                return response()->json(['success' => false, 'message' => 'Not found', 'count' => 0], 404);
            }

            $f = $response->json()['fields'] ?? [];

            return response()->json([
                'success' => true,
                'message' => 'Video retrieved successfully',
                'count'   => 1,
                'data' => [
                    'id'            => $id,
                    'courseid'      => $f['courseid']['stringValue'] ?? '',
                    'lessonid'      => $f['lessonid']['stringValue'] ?? '',
                    'title'         => $f['title']['stringValue'] ?? '',
                    'description'   => $f['description']['stringValue'] ?? '',
                    'videourl'      => $f['videourl']['stringValue'] ?? '',
                    'prerequisites' => $this->pluckStringArray($f['prerequisites'] ?? null),
                    'resources'     => $this->pluckStringArray($f['resources'] ?? null),
                    'createdAt'     => $f['createdAt']['timestampValue'] ?? '',
                    'updatedAt'     => $f['updatedAt']['timestampValue'] ?? '',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:getVideo] FAILED', [
                'id' => $id, 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'count' => 0], 500);
        }
    }

    public function destroy(Request $request, string $id)
    {
        try {
            $projectId   = env('FIREBASE_PROJECT_ID');
            $courseId    = $request->query('courseid',    $request->input('courseid', ''));
            $lessonId    = $request->query('lessonid',    $request->input('lessonid', ''));
            $lessonDocId = $request->query('lessonDocId', $request->input('lessonDocId', ''));

            if (!$courseId) {
                return response()->json(['success' => false, 'message' => 'courseid is required', 'count' => 0], 422);
            }

            $authToken = app(FirestoreServiceForVideos::class);
            $reflection = new \ReflectionClass($authToken);
            $method = $reflection->getMethod('token');
            $method->setAccessible(true);
            $bearer = $method->invoke($authToken);

            if (!$bearer) {
                return response()->json(['success' => false, 'message' => 'Not authenticated', 'count' => 0], 401);
            }

            if (!$lessonDocId && $lessonId) {
                $lessonDocId = $this->firestore->findLessonDocIdByField($courseId, $lessonId);
            }

            // DELETE 1
            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])->timeout(30)->delete($url);
            $videoDeleted = $response->successful() || $response->status() === 404;

            // DELETE 2
            $lessonDeleted = false;
            $lessonError = null;
            if ($lessonDocId) {
                try {
                    $lessonDeleted = $this->firestore->deleteLesson($courseId, $lessonDocId);
                } catch (\Throwable $e) {
                    $lessonError = $e->getMessage();
                    Log::warning('[video:destroy] lesson delete FAILED', [
                        'courseid' => $courseId, 'lessonDocId' => $lessonDocId, 'error' => $e->getMessage(),
                    ]);
                }
            }

            if (!$videoDeleted && !$lessonDeleted) {
                return response()->json(['success' => false, 'message' => 'Failed to delete from both locations', 'count' => 0], 500);
            }

            ActivityLogger::log(
                'admin_action',
                'Video deleted',
                'Video ' . $id . ' deleted by ' . session('firebase_username', 'Admin'),
                ['courseid' => $courseId, 'lessonDocId' => $lessonDocId]
            );

            return response()->json([
                'success' => true,
                'message' => 'Video deleted',
                'count'   => 1,
                'data' => [
                    'id'          => $id,
                    'courseid'    => $courseId,
                    'lessonid'    => $lessonId,
                    'lessonDocId' => $lessonDocId,
                    'writes' => [
                        'videos'        => $videoDeleted,
                        'lessons'       => $lessonDeleted,
                        'lessons_error' => $lessonError,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:destroy] FAILED', [
                'id' => $id, 'message' => $e->getMessage(),
                'file' => $e->getFile(), 'line' => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'count' => 0], 500);
        }
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
