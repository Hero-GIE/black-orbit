<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VideoUploadRequest;
use App\Services\ActivityLogger;
use App\Services\CloudinaryService;
use App\Services\FirestoreServiceForVideos;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
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

            // If creating, a file or URL is required
            if (!$isUpdating && !$file && !$urlFromInput) {
                return response()->json(['success' => false, 'message' => 'Video file or URL is required'], 422);
            }

            $payload = [
                'courseid'      => $request->input('courseid'),
                'lessonid'      => $request->input('lessonid'),
                'title'         => $request->input('title'),
                'description'   => $request->input('description', ''),
                'prerequisites' => $request->input('prerequisites', []),
                'resources'     => $request->input('resources', []),
                'updatedAt'     => now()->toISOString(),
            ];

            if ($file) {
                // Upload to Cloudinary
                $publicId = "{$request->input('lessonid')}_" . time();
                Log::info('[video:upload] uploading to Cloudinary', ['public_id' => $publicId]);

                $cloud = $this->cloudinary->uploadVideo($file, [
                    'folder'    => "videos/{$request->input('courseid')}",
                    'public_id' => $publicId,
                    'tags'      => ["course:{$request->input('courseid')}", "lesson:{$request->input('lessonid')}"],
                    'context'   => [
                        'title'    => $request->input('title'),
                        'lessonid' => $request->input('lessonid'),
                        'courseid' => $request->input('courseid'),
                    ],
                ]);

                $payload['videourl']           = $cloud['url'];
                $payload['cloudinaryPublicId'] = $cloud['public_id'];
                $payload['duration']           = $cloud['duration'];
                $payload['bytes']              = $cloud['bytes'];
            } elseif ($urlFromInput) {
                // Use the pasted URL
                $payload['videourl']           = $urlFromInput;
                $payload['cloudinaryPublicId'] = null;
                $payload['duration']           = null;
                $payload['bytes']              = null;
            }

            if (!$isUpdating) {
                $payload['createdAt'] = now()->toISOString();
            }

            Log::info('[video:upload] writing to Firestore', ['docId' => $docId]);
            $this->firestore->createVideo($docId, $payload);
            Log::info('[video:upload] Firestore write OK', ['docId' => $docId]);

            ActivityLogger::log(
                'admin_action',
                ($isUpdating ? 'Video updated: ' : 'Video uploaded: ') . $request->input('title'),
                'Action by ' . session('firebase_username', 'Admin'),
                ['docId' => $docId]
            );

            return response()->json([
                'success' => true,
                'message' => $isUpdating ? 'Video updated successfully' : 'Video uploaded successfully',
                'data' => [
                    'id'       => $docId,
                    'videourl' => $payload['videourl'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:upload] FAILED', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function replaceVideo(Request $request, string $id)
    {
        Log::info('[video:replace] START', ['id' => $id, 'has_file' => $request->hasFile('video')]);

        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:' . config('services.videos.max_size_kb', 512000),
        ]);

        try {
            Log::info('[video:replace] uploading to Cloudinary');

            $cloud = $this->cloudinary->uploadVideo($request->file('video'), [
                'folder' => 'videos/replacements',
            ]);

            Log::info('[video:replace] Cloudinary OK', ['url' => $cloud['url'] ?? null]);

            $this->firestore->updateVideoUrl($id, $cloud['url']);

            Log::info('[video:replace] Firestore updated', ['id' => $id]);

            ActivityLogger::log('admin_action', 'Video replaced', 'Video ' . $id . ' replaced by ' . session('firebase_username', 'Admin'));

            return response()->json([
                'success' => true,
                'message' => 'Video replaced successfully',
                'data' => ['videourl' => $cloud['url']],
            ]);
        } catch (\Throwable $e) {
            Log::error('[video:replace] FAILED', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

       public function fetchVideos(Request $request)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = session('firebase_token');

            Log::info('[video:fetchVideos] START', [
                'has_session_token' => (bool) $token,
                'filters' => [
                    'courseid' => $request->query('courseid'),
                    'lessonid' => $request->query('lessonid'),
                    'q'        => $request->query('q'),
                    'limit'    => $request->query('limit'),
                ],
            ]);

            $limit = min((int) $request->query('limit', 24), 100);
            $search = trim((string) $request->query('q', ''));
            $courseid = trim((string) $request->query('courseid', ''));
            $lessonid = trim((string) $request->query('lessonid', ''));

            // Use the service for auth — service account preferred, session fallback
            $authToken = app(FirestoreServiceForVideos::class);
            $reflection = new \ReflectionClass($authToken);
            $method = $reflection->getMethod('token');
            $method->setAccessible(true);
            $bearer = $method->invoke($authToken);

            if (!$bearer) {
                Log::warning('[video:fetchVideos] no auth token available');
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos";
            $videos = [];
            $pageToken = null;

            // Fetch all matching documents to sort them properly in PHP
            for ($page = 0; $page < 30; $page++) {
                $query = ['pageSize' => 300];
                if ($pageToken) $query['pageToken'] = $pageToken;

                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                    ->timeout(30)
                    ->get($baseUrl . '?' . http_build_query($query));

                if (!$response->successful()) {
                    Log::error('[video:fetchVideos] Firestore HTTP error', [
                        'status' => $response->status(),
                        'body'   => substr($response->body(), 0, 500),
                        'page'   => $page,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Firestore auth failed (HTTP ' . $response->status() . ')',
                    ], 500);
                }

                $data = $response->json();

                foreach ($data['documents'] ?? [] as $doc) {
                    $f = $doc['fields'] ?? [];
                    $video = [
                        'id' => basename($doc['name']),
                        'courseid' => $f['courseid']['stringValue'] ?? '',
                        'lessonid' => $f['lessonid']['stringValue'] ?? '',
                        'title' => $f['title']['stringValue'] ?? '',
                        'description' => $f['description']['stringValue'] ?? '',
                        'videourl' => $f['videourl']['stringValue'] ?? '',
                        'prerequisites' => $this->pluckStringArray($f['prerequisites'] ?? null),
                        'resources' => $this->pluckStringArray($f['resources'] ?? null),
                        'createdAt' => $f['createdAt']['timestampValue'] ?? '',
                        'updatedAt' => $f['updatedAt']['timestampValue'] ?? '',
                    ];

                    // Apply filters
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
                if ($courseCmp !== 0) {
                    return $courseCmp;
                }
                return strnatcmp($a['lessonid'] ?? '', $b['lessonid'] ?? '');
            });

            Log::info('[video:fetchVideos] OK', [
                'returned' => count($videos),
                'has_more' => false,
            ]);

            return response()->json(['success' => true, 'data' => $videos, 'nextPageToken' => null]);
        } catch (\Throwable $e) {
            Log::error('[video:fetchVideos] FAILED', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function fetchCourses()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');

            Log::info('[video:fetchCourses] START');

            $authToken = app(FirestoreServiceForVideos::class);
            $reflection = new \ReflectionClass($authToken);
            $method = $reflection->getMethod('token');
            $method->setAccessible(true);
            $bearer = $method->invoke($authToken);

            if (!$bearer) {
                Log::warning('[video:fetchCourses] no auth token available');
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
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
                    Log::error('[video:fetchCourses] Firestore HTTP error', [
                        'status' => $response->status(),
                        'body'   => substr($response->body(), 0, 500),
                        'page'   => $page,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Firestore auth failed (HTTP ' . $response->status() . ')',
                    ], 500);
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

            Log::info('[video:fetchCourses] OK', ['count' => count($courses), 'courses' => $courses]);

            return response()->json(['success' => true, 'data' => $courses]);
        } catch (\Throwable $e) {
            Log::error('[video:fetchCourses] FAILED', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

        public function fetchLessons()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');

            Log::info('[video:fetchLessons] START');

            $authToken = app(FirestoreServiceForVideos::class);
            $reflection = new \ReflectionClass($authToken);
            $method = $reflection->getMethod('token');
            $method->setAccessible(true);
            $bearer = $method->invoke($authToken);

            if (!$bearer) {
                Log::warning('[video:fetchLessons] no auth token available');
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
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
                    Log::error('[video:fetchLessons] Firestore HTTP error', [
                        'status' => $response->status(),
                        'body'   => substr($response->body(), 0, 500),
                        'page'   => $page,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Firestore auth failed (HTTP ' . $response->status() . ')',
                    ], 500);
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

            Log::info('[video:fetchLessons] OK', ['count' => count($lessons), 'lessons' => $lessons]);

            return response()->json(['success' => true, 'data' => $lessons]);
        } catch (\Throwable $e) {
            Log::error('[video:fetchLessons] FAILED', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getVideo(string $id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');

            Log::info('[video:getVideo] START', ['id' => $id]);

            $authToken = app(FirestoreServiceForVideos::class);
            $reflection = new \ReflectionClass($authToken);
            $method = $reflection->getMethod('token');
            $method->setAccessible(true);
            $bearer = $method->invoke($authToken);

            if (!$bearer) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])->timeout(30)->get($url);

            if (!$response->successful()) {
                Log::warning('[video:getVideo] not found', [
                    'id'     => $id,
                    'status' => $response->status(),
                ]);
                return response()->json(['success' => false, 'message' => 'Not found'], 404);
            }

            $f = $response->json()['fields'] ?? [];

            Log::info('[video:getVideo] OK', ['id' => $id]);

            return response()->json(['success' => true, 'data' => [
                'id' => $id,
                'courseid' => $f['courseid']['stringValue'] ?? '',
                'lessonid' => $f['lessonid']['stringValue'] ?? '',
                'title' => $f['title']['stringValue'] ?? '',
                'description' => $f['description']['stringValue'] ?? '',
                'videourl' => $f['videourl']['stringValue'] ?? '',
                'prerequisites' => $this->pluckStringArray($f['prerequisites'] ?? null),
                'resources' => $this->pluckStringArray($f['resources'] ?? null),
                'createdAt' => $f['createdAt']['timestampValue'] ?? '',
                'updatedAt' => $f['updatedAt']['timestampValue'] ?? '',
            ]]);
        } catch (\Throwable $e) {
            Log::error('[video:getVideo] FAILED', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');

            Log::info('[video:destroy] START', ['id' => $id]);

            $authToken = app(FirestoreServiceForVideos::class);
            $reflection = new \ReflectionClass($authToken);
            $method = $reflection->getMethod('token');
            $method->setAccessible(true);
            $bearer = $method->invoke($authToken);

            if (!$bearer) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])->timeout(30)->delete($url);

            if ($response->successful()) {
                Log::info('[video:destroy] OK', ['id' => $id]);
                return response()->json(['success' => true, 'message' => 'Video deleted']);
            }

            Log::error('[video:destroy] Firestore error', [
                'id'     => $id,
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 300),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
        } catch (\Throwable $e) {
            Log::error('[video:destroy] FAILED', [
                'id'      => $id,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
}
