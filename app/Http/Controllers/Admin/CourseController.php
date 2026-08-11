<?php
// app/Http/Controllers/Admin/CourseController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CourseController extends Controller
{
    public function index()
    {
        return view('admin.courses.index');
    }

    private function getFirebaseToken()
    {
        return session('firebase_token');
    }

    public function fetchCourses()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $courses = [];

                foreach ($data['documents'] ?? [] as $doc) {
                    $fields = $doc['fields'] ?? [];

                    $enrolledUsers = [];
                    if (isset($fields['enrolledUsers']['arrayValue'])) {
                        $values = $fields['enrolledUsers']['arrayValue']['values'] ?? [];
                        foreach ($values as $value) {
                            if (isset($value['stringValue'])) {
                                $enrolledUsers[] = $value['stringValue'];
                            }
                        }
                    }

                    $lessonCount = intval($fields['lessoncount']['integerValue'] ?? 0);

                    $courses[] = [
                        'id' => basename($doc['name']),
                        'courseid' => $fields['courseid']['stringValue'] ?? '',
                        'coursename' => $fields['coursename']['stringValue'] ?? '',
                        'category' => $fields['category']['stringValue'] ?? 'General',
                        'courseLevel' => $fields['courseLevel']['stringValue'] ?? 'Beginner',
                        'coursedescription' => $fields['coursedescription']['stringValue'] ?? '',
                        'image' => $fields['image']['stringValue'] ?? '',
                        'lessoncount' => $lessonCount,
                        'certificate' => $fields['certificate']['booleanValue'] ?? false,
                        'enrolledUsers' => $enrolledUsers,
                        'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                        'updatedAt' => $fields['updatedAt']['timestampValue'] ?? '',
                    ];
                }

                return response()->json([
                    'success' => true,
                    'data' => $courses
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch courses'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching courses: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCourse($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$id}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $fields = $data['fields'] ?? [];

                $enrolledUsers = [];
                if (isset($fields['enrolledUsers']['arrayValue'])) {
                    $values = $fields['enrolledUsers']['arrayValue']['values'] ?? [];
                    foreach ($values as $value) {
                        if (isset($value['stringValue'])) {
                            $enrolledUsers[] = $value['stringValue'];
                        }
                    }
                }

                $course = [
                    'id' => $id,
                    'courseid' => $fields['courseid']['stringValue'] ?? '',
                    'coursename' => $fields['coursename']['stringValue'] ?? '',
                    'category' => $fields['category']['stringValue'] ?? 'General',
                    'courseLevel' => $fields['courseLevel']['stringValue'] ?? 'Beginner',
                    'coursedescription' => $fields['coursedescription']['stringValue'] ?? '',
                    'image' => $fields['image']['stringValue'] ?? '',
                    'lessoncount' => intval($fields['lessoncount']['integerValue'] ?? 0),
                    'certificate' => $fields['certificate']['booleanValue'] ?? false,
                    'enrolledUsers' => $enrolledUsers,
                    'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                    'updatedAt' => $fields['updatedAt']['timestampValue'] ?? '',
                ];

                return response()->json([
                    'success' => true,
                    'data' => $course
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Course not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching course: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'coursename' => 'required|string|max:255',
                'category' => 'required|string',
                'courseLevel' => 'required|string',
                'coursedescription' => 'nullable|string',
                'image' => 'nullable|url',
                'lessoncount' => 'nullable|integer',
                'certificate' => 'nullable|boolean',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $courseId = uniqid() . '_' . time();

            $courseData = [
                'fields' => [
                    'courseid' => ['stringValue' => $courseId],
                    'coursename' => ['stringValue' => $request->coursename],
                    'category' => ['stringValue' => $request->category],
                    'courseLevel' => ['stringValue' => $request->courseLevel],
                    'coursedescription' => ['stringValue' => $request->coursedescription ?? ''],
                    'image' => ['stringValue' => $request->image ?? ''],
                    'lessoncount' => ['integerValue' => 0],
                    'certificate' => ['booleanValue' => boolval($request->certificate ?? false)],
                    'enrolledUsers' => ['arrayValue' => ['values' => []]],
                    'createdAt' => ['timestampValue' => now()->toISOString()],
                    'updatedAt' => ['timestampValue' => now()->toISOString()],
                ]
            ];

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$courseId}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->patch($url, $courseData);

            if ($response->successful()) {
                ActivityLogger::log(
                    'course_created',
                    'Course created: ' . $request->coursename,
                    'A new course was added by ' . session('firebase_username', 'Admin'),
                    [
                        'category' => $request->category,
                        'courseId' => $courseId
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Course created successfully',
                    'data' => ['id' => $courseId]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create course: ' . $response->body()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating course: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'coursename' => 'required|string|max:255',
                'category' => 'required|string',
                'courseLevel' => 'required|string',
                'coursedescription' => 'nullable|string',
                'image' => 'nullable|url',
                'lessoncount' => 'nullable|integer',
                'certificate' => 'nullable|boolean',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $getUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$id}";
            $getResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get($getUrl);

            if (!$getResponse->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            $existingData = $getResponse->json();
            $existingFields = $existingData['fields'] ?? [];

            $updateFields = [
                'coursename' => ['stringValue' => $request->coursename],
                'category' => ['stringValue' => $request->category],
                'courseLevel' => ['stringValue' => $request->courseLevel],
                'coursedescription' => ['stringValue' => $request->coursedescription ?? ''],
                'image' => ['stringValue' => $request->image ?? ''],
                'certificate' => ['booleanValue' => boolval($request->certificate ?? false)],
                'updatedAt' => ['timestampValue' => now()->toISOString()],
            ];

            if (isset($existingFields['lessoncount'])) {
                $updateFields['lessoncount'] = $existingFields['lessoncount'];
            }

            if (isset($existingFields['courseid'])) {
                $updateFields['courseid'] = $existingFields['courseid'];
            }

            if (isset($existingFields['createdAt'])) {
                $updateFields['createdAt'] = $existingFields['createdAt'];
            }

            $courseData = ['fields' => $updateFields];

            $patchUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$id}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->patch($patchUrl, $courseData);

            if ($response->successful()) {
                ActivityLogger::log(
                    'course_updated',
                    'Course updated: ' . $request->coursename,
                    'Course details were modified by ' . session('firebase_username', 'Admin'),
                    [
                        'category' => $request->category,
                        'courseId' => $id
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Course updated successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update course: ' . $response->body()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating course: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$id}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->delete($url);

            if ($response->successful()) {

                ActivityLogger::log(
                    'admin_action',
                    'Course deleted',
                    'A course was removed by ' . session('firebase_username', 'Admin'),
                    [
                        'courseId' => $id
                    ]
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Course deleted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting course: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showLessons($courseId)
    {
        return view('admin.lessons.index', compact('courseId'));
    }

    public function fetchLessons($courseId)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$courseId}/lessons";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get($url);

            if ($response->status() === 403) {
                $altUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/lessons";
                $altResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->get($altUrl);

                if ($altResponse->successful()) {
                    $data = $altResponse->json();
                    $documents = $data['documents'] ?? [];

                    $lessons = [];
                    foreach ($documents as $doc) {
                        $fields = $doc['fields'] ?? [];
                        $docCourseId = $fields['courseid']['stringValue'] ?? '';

                        if ($docCourseId === $courseId) {
                            $resources = [];
                            if (isset($fields['resources']['arrayValue'])) {
                                $values = $fields['resources']['arrayValue']['values'] ?? [];
                                foreach ($values as $value) {
                                    if (isset($value['stringValue'])) {
                                        $resources[] = $value['stringValue'];
                                    }
                                }
                            }

                            $lessons[] = [
                                'id' => basename($doc['name']),
                                'lessonid' => $fields['lessonid']['stringValue'] ?? '',
                                'title' => $fields['title']['stringValue'] ?? '',
                                'description' => $fields['description']['stringValue'] ?? '',
                                'courseid' => $fields['courseid']['stringValue'] ?? $courseId,
                                'videourl' => $fields['videourl']['stringValue'] ?? '',
                                'resources' => $resources,
                                'createdAt' => $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? '',
                                'updatedAt' => $fields['updatedAt']['timestampValue'] ?? $fields['updatedAt']['stringValue'] ?? '',
                            ];
                        }
                    }

                    return response()->json([
                        'success' => true,
                        'data' => $lessons,
                        'course_id' => $courseId
                    ]);
                }
            }

            if ($response->successful()) {
                $data = $response->json();
                $lessons = [];

                foreach ($data['documents'] ?? [] as $doc) {
                    $fields = $doc['fields'] ?? [];

                    $resources = [];
                    if (isset($fields['resources']['arrayValue'])) {
                        $values = $fields['resources']['arrayValue']['values'] ?? [];
                        foreach ($values as $value) {
                            if (isset($value['stringValue'])) {
                                $resources[] = $value['stringValue'];
                            }
                        }
                    }

                    $lessons[] = [
                        'id' => basename($doc['name']),
                        'lessonid' => $fields['lessonid']['stringValue'] ?? '',
                        'title' => $fields['title']['stringValue'] ?? '',
                        'description' => $fields['description']['stringValue'] ?? '',
                        'courseid' => $fields['courseid']['stringValue'] ?? $courseId,
                        'videourl' => $fields['videourl']['stringValue'] ?? '',
                        'resources' => $resources,
                        'createdAt' => $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? '',
                        'updatedAt' => $fields['updatedAt']['timestampValue'] ?? $fields['updatedAt']['stringValue'] ?? '',
                    ];
                }

                return response()->json([
                    'success' => true,
                    'data' => $lessons,
                    'course_id' => $courseId
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lessons: ' . $response->body()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching lessons: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getLesson($courseId, $lessonId)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$courseId}/lessons/{$lessonId}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get($url);

            if ($response->status() === 403) {
                $altUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/lessons/{$lessonId}";
                $altResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->get($altUrl);

                if ($altResponse->successful()) {
                    $data = $altResponse->json();
                    $fields = $data['fields'] ?? [];

                    $resources = [];
                    if (isset($fields['resources']['arrayValue'])) {
                        $values = $fields['resources']['arrayValue']['values'] ?? [];
                        foreach ($values as $value) {
                            if (isset($value['stringValue'])) {
                                $resources[] = $value['stringValue'];
                            }
                        }
                    }

                    $lesson = [
                        'id' => $lessonId,
                        'lessonid' => $fields['lessonid']['stringValue'] ?? '',
                        'title' => $fields['title']['stringValue'] ?? '',
                        'description' => $fields['description']['stringValue'] ?? '',
                        'courseid' => $fields['courseid']['stringValue'] ?? $courseId,
                        'videourl' => $fields['videourl']['stringValue'] ?? '',
                        'resources' => $resources,
                        'createdAt' => $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? '',
                        'updatedAt' => $fields['updatedAt']['timestampValue'] ?? $fields['updatedAt']['stringValue'] ?? '',
                    ];

                    return response()->json([
                        'success' => true,
                        'data' => $lesson
                    ]);
                }
            }

            if ($response->successful()) {
                $data = $response->json();
                $fields = $data['fields'] ?? [];

                $resources = [];
                if (isset($fields['resources']['arrayValue'])) {
                    $values = $fields['resources']['arrayValue']['values'] ?? [];
                    foreach ($values as $value) {
                        if (isset($value['stringValue'])) {
                            $resources[] = $value['stringValue'];
                        }
                    }
                }

                $lesson = [
                    'id' => $lessonId,
                    'lessonid' => $fields['lessonid']['stringValue'] ?? '',
                    'title' => $fields['title']['stringValue'] ?? '',
                    'description' => $fields['description']['stringValue'] ?? '',
                    'courseid' => $fields['courseid']['stringValue'] ?? $courseId,
                    'videourl' => $fields['videourl']['stringValue'] ?? '',
                    'resources' => $resources,
                    'createdAt' => $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? '',
                    'updatedAt' => $fields['updatedAt']['timestampValue'] ?? $fields['updatedAt']['stringValue'] ?? '',
                ];

                return response()->json([
                    'success' => true,
                    'data' => $lesson
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Lesson not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching lesson: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeLesson(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'courseid' => 'required|string',
                'videourl' => 'nullable|url',
                'resources' => 'nullable|string',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $lessonId = uniqid() . '_' . time();

            $resources = [];
            if ($request->resources) {
                $resources = array_filter(array_map('trim', explode("\n", $request->resources)));
            }

            $resourcesValues = [];
            foreach ($resources as $resource) {
                $resourcesValues[] = ['stringValue' => $resource];
            }

            $lessonData = [
                'fields' => [
                    'lessonid' => ['stringValue' => $lessonId],
                    'title' => ['stringValue' => $request->title],
                    'description' => ['stringValue' => $request->description ?? ''],
                    'courseid' => ['stringValue' => $request->courseid],
                    'videourl' => ['stringValue' => $request->videourl ?? ''],
                    'resources' => ['arrayValue' => ['values' => $resourcesValues]],
                    'createdAt' => ['timestampValue' => now()->toISOString()],
                    'updatedAt' => ['timestampValue' => now()->toISOString()],
                ]
            ];

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$request->courseid}/lessons/{$lessonId}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->patch($url, $lessonData);

            if ($response->status() === 403) {
                $altUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/lessons/{$lessonId}";
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->patch($altUrl, $lessonData);
            }

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create lesson'
                ], 500);
            }

            $this->updateLessonCount($request->courseid, $token, $projectId);

            return response()->json([
                'success' => true,
                'message' => 'Lesson created successfully',
                'data' => ['id' => $lessonId]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating lesson: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateLesson(Request $request, $courseId, $lessonId)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'videourl' => 'nullable|url',
                'resources' => 'nullable|string',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $resources = [];
            if ($request->resources) {
                $resources = array_filter(array_map('trim', explode("\n", $request->resources)));
            }

            $resourcesValues = [];
            foreach ($resources as $resource) {
                $resourcesValues[] = ['stringValue' => $resource];
            }

            $updateFields = [
                'title' => ['stringValue' => $request->title],
                'description' => ['stringValue' => $request->description ?? ''],
                'videourl' => ['stringValue' => $request->videourl ?? ''],
                'resources' => ['arrayValue' => ['values' => $resourcesValues]],
                'updatedAt' => ['timestampValue' => now()->toISOString()],
            ];

            $getUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$courseId}/lessons/{$lessonId}";
            $getResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get($getUrl);

            $existingFields = [];

            if ($getResponse->successful()) {
                $existingData = $getResponse->json();
                $existingFields = $existingData['fields'] ?? [];
            } elseif ($getResponse->status() === 403) {
                $altUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/lessons/{$lessonId}";
                $altResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->get($altUrl);

                if ($altResponse->successful()) {
                    $existingData = $altResponse->json();
                    $existingFields = $existingData['fields'] ?? [];
                }
            }

            if (isset($existingFields['lessonid'])) {
                $updateFields['lessonid'] = $existingFields['lessonid'];
            }
            if (isset($existingFields['courseid'])) {
                $updateFields['courseid'] = $existingFields['courseid'];
            }
            if (isset($existingFields['createdAt'])) {
                $updateFields['createdAt'] = $existingFields['createdAt'];
            }

            $lessonData = ['fields' => $updateFields];

            $patchUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$courseId}/lessons/{$lessonId}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->patch($patchUrl, $lessonData);

            if ($response->status() === 403) {
                $altPatchUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/lessons/{$lessonId}";
                $altResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->patch($altPatchUrl, $lessonData);

                if ($altResponse->successful()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Lesson updated successfully'
                    ]);
                }
            }

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Lesson updated successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson: ' . $response->body()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating lesson: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyLesson($courseId, $lessonId)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$courseId}/lessons/{$lessonId}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->delete($url);

            if ($response->status() === 403) {
                $altUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/lessons/{$lessonId}";
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->delete($altUrl);
            }

            if ($response->successful()) {
                $this->updateLessonCount($courseId, $token, $projectId);

                return response()->json([
                    'success' => true,
                    'message' => 'Lesson deleted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lesson'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting lesson: ' . $e->getMessage()
            ], 500);
        }
    }

    private function updateLessonCount($courseId, $token, $projectId)
    {
        try {
            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$courseId}/lessons";
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $lessonCount = count($data['documents'] ?? []);

                $updateData = [
                    'fields' => [
                        'lessoncount' => ['integerValue' => $lessonCount],
                        'updatedAt' => ['timestampValue' => now()->toISOString()],
                    ]
                ];

                $courseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$courseId}";
                $courseResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->get($courseUrl);

                if ($courseResponse->successful()) {
                    $courseData = $courseResponse->json();
                    $existingFields = $courseData['fields'] ?? [];

                    foreach ($existingFields as $key => $value) {
                        if (!isset($updateData['fields'][$key]) && $key !== 'lessoncount' && $key !== 'updatedAt') {
                            $updateData['fields'][$key] = $value;
                        }
                    }
                }

                $patchUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses/{$courseId}";
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->patch($patchUrl, $updateData);
            }

        } catch (\Exception $e) {
            // Silently fail lesson count update to prevent blocking the main operation
        }
    }
}
