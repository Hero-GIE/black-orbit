<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\VideoUploadRequest;
use App\Services\ActivityLogger;
use App\Services\CloudinaryService;
use App\Services\FireStoreServiceForVideos;
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
}
