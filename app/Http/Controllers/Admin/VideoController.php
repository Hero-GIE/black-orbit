<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VideoUploadRequest;
use App\Services\ActivityLogger;
use App\Services\CloudinaryService;
use App\Services\FireStoreServiceForVideos;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    protected CloudinaryService $cloudinary;
    protected FireStoreServiceForVideos $firestore;

    public function __construct(CloudinaryService $cloudinary, FireStoreServiceForVideos $firestore)
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
        try {
            $file = $request->file('video');
            $courseid = $request->input('courseid');
            $lessonid = $request->input('lessonid');

            $docId = Str::uuid()->toString();
            $publicId = "videos/{$courseid}/{$lessonid}_" . time();

            $cloud = $this->cloudinary->uploadVideo($file, [
                'folder'    => "videos/{$courseid}",
                'public_id' => $publicId,
                'tags'      => ["course:{$courseid}", "lesson:{$lessonid}"],
                'context'   => [
                    'title'    => $request->input('title'),
                    'lessonid' => $lessonid,
                    'courseid' => $courseid,
                ],
            ]);

            $this->firestore->createVideo($docId, [
                'courseid'      => $courseid,
                'lessonid'      => $lessonid,
                'title'         => $request->input('title'),
                'description'   => $request->input('description', ''),
                'videourl'      => $cloud['url'],
                'prerequisites' => $request->input('prerequisites', []),
                'resources'     => $request->input('resources', []),
                'createdAt'     => now()->toISOString(),
                'updatedAt'     => now()->toISOString(),
                'cloudinary_public_id' => $cloud['public_id'],
                'duration'      => $cloud['duration'],
                'bytes'         => $cloud['bytes'],
            ]);

            ActivityLogger::log(
                'admin_action',
                'Video uploaded: ' . $request->input('title'),
                'Uploaded by ' . session('firebase_username', 'Admin'),
                ['docId' => $docId, 'url' => $cloud['url']]
            );

            return response()->json([
                'success' => true,
                'message' => 'Video uploaded successfully',
                'data' => [
                    'id'       => $docId,
                    'videourl' => $cloud['url'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function replaceVideo(Request $request, string $id)
    {
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:' . env('VIDEO_MAX_SIZE', 512000),
        ]);

        try {
            $cloud = $this->cloudinary->uploadVideo($request->file('video'), [
                'folder' => 'videos/replacements',
            ]);

            $this->firestore->updateVideoUrl($id, $cloud['url']);

            ActivityLogger::log('admin_action', 'Video replaced', 'Video ' . $id . ' replaced by ' . session('firebase_username', 'Admin'));

            return response()->json(['success' => true, 'message' => 'Video replaced successfully', 'data' => ['videourl' => $cloud['url']]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function fetchVideos(Request $request)
{
    try {
        $projectId = env('FIREBASE_PROJECT_ID');
        $token = session('firebase_token');
        if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

        $limit = min((int) $request->query('limit', 24), 100);
        $startToken = $request->query('page_token');
        $search = trim((string) $request->query('q', ''));
        $courseid = trim((string) $request->query('courseid', ''));
        $lessonid = trim((string) $request->query('lessonid', ''));

        $baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos";
        $videos = [];
        $pageToken = $startToken;
        $nextToken = null;

        for ($page = 0; $page < 30; $page++) {
            $query = ['pageSize' => 300];
            if ($pageToken) $query['pageToken'] = $pageToken;

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->timeout(30)
                ->get($baseUrl . '?' . http_build_query($query));

            if (!$response->successful()) return response()->json(['success' => false, 'message' => 'Failed to fetch'], 500);

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

                if ($courseid && $video['courseid'] !== $courseid) continue;
                if ($lessonid && $video['lessonid'] !== $lessonid) continue;
                if ($search) {
                    $q = mb_strtolower($search);
                    if (!str_contains(mb_strtolower($video['title']), $q)
                        && !str_contains(mb_strtolower($video['courseid']), $q)
                        && !str_contains(mb_strtolower($video['lessonid']), $q)) continue;
                }

                $videos[] = $video;
                if (count($videos) >= $limit) {
                    $nextToken = $pageToken ?? ($data['nextPageToken'] ?? null);
                    break 2;
                }
            }

            $pageToken = $data['nextPageToken'] ?? null;
            if (!$pageToken) { $nextToken = null; break; }
        }

        return response()->json(['success' => true, 'data' => $videos, 'nextPageToken' => $nextToken]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

public function fetchCourses()
{
    try {
        $projectId = env('FIREBASE_PROJECT_ID');
        $token = session('firebase_token');
        if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

        $baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos";
        $courses = [];
        $pageToken = null;

        for ($page = 0; $page < 50; $page++) {
            $query = ['pageSize' => 300];
            if ($pageToken) $query['pageToken'] = $pageToken;

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->timeout(30)->get($baseUrl . '?' . http_build_query($query));
            if (!$response->successful()) break;

            $data = $response->json();
            foreach ($data['documents'] ?? [] as $doc) {
                $c = $doc['fields']['courseid']['stringValue'] ?? '';
                if ($c !== '' && !in_array($c, $courses, true)) $courses[] = $c;
            }
            $pageToken = $data['nextPageToken'] ?? null;
            if (!$pageToken) break;
        }

        sort($courses, SORT_NATURAL | SORT_FLAG_CASE);
        return response()->json(['success' => true, 'data' => $courses]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

public function getVideo(string $id)
{
    try {
        $projectId = env('FIREBASE_PROJECT_ID');
        $token = session('firebase_token');
        if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos/{$id}";
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

        if (!$response->successful()) return response()->json(['success' => false, 'message' => 'Not found'], 404);

        $f = $response->json()['fields'] ?? [];
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
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

public function destroy(string $id)
{
    try {
        $projectId = env('FIREBASE_PROJECT_ID');
        $token = session('firebase_token');
        if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

        $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/videos/{$id}";
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->delete($url);

        if ($response->successful()) {
            return response()->json(['success' => true, 'message' => 'Video deleted']);
        }
        return response()->json(['success' => false, 'message' => 'Failed to delete'], 500);
    } catch (\Exception $e) {
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
