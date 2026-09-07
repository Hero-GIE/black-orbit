<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EnrollmentController extends Controller
{
    public function index()
    {
        return view('admin.enrollments.index');
    }

    private function getFirebaseToken()
    {
        $token = session('firebase_token');
        if (empty($token)) {
            return null;
        }
        return $token;
    }

    public function fetchEnrollments()
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

            $usersResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get(
                "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users"
            );

            $coursesResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get(
                "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses"
            );

            $enrollments = [];

            if ($usersResponse->successful() && $coursesResponse->successful()) {
                $usersData = $usersResponse->json();
                $coursesData = $coursesResponse->json();

                $users = [];
                foreach ($usersData['documents'] ?? [] as $doc) {
                    $fields = $doc['fields'] ?? [];
                    $users[] = [
                        'id' => basename($doc['name']),
                        'username' => $fields['username']['stringValue'] ?? '',
                        'email' => $fields['email']['stringValue'] ?? '',
                        'enrolledCourses' => $this->parseEnrolledCourses($fields['enrolledCourses'] ?? null),
                        'createdAt' => $fields['createdAt']['stringValue'] ?? $fields['createdAt']['timestampValue'] ?? '',
                    ];
                }

                $courses = [];
                foreach ($coursesData['documents'] ?? [] as $doc) {
                    $fields = $doc['fields'] ?? [];
                    $courses[] = [
                        'id' => basename($doc['name']),
                        'courseid' => $fields['courseid']['stringValue'] ?? '',
                        'coursename' => $fields['coursename']['stringValue'] ?? '',
                        'category' => $fields['category']['stringValue'] ?? '',
                        'courseLevel' => $fields['courseLevel']['stringValue'] ?? '',
                        'lessoncount' => $fields['lessoncount']['integerValue'] ?? 0,
                        'enrolledUsers' => $this->parseEnrolledUsers($fields['enrolledUsers'] ?? null),
                        'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                    ];
                }

                foreach ($users as $user) {
                    foreach ($user['enrolledCourses'] as $courseId) {
                        $courseDetails = null;
                        foreach ($courses as $course) {
                            if ($course['courseid'] === $courseId || $course['id'] === $courseId) {
                                $courseDetails = $course;
                                break;
                            }
                        }

                        if ($courseDetails) {
                            $enrollments[] = [
                                'user_id' => $user['id'],
                                'username' => $user['username'],
                                'email' => $user['email'],
                                'course_id' => $courseDetails['id'],
                                'course_name' => $courseDetails['coursename'],
                                'category' => $courseDetails['category'] ?? 'General',
                                'level' => $courseDetails['courseLevel'] ?? 'Beginner',
                                'enrolled_date' => $user['createdAt'] ?? date('Y-m-d'),
                                'progress' => rand(0, 100),
                                'status' => ['Active', 'In Progress', 'Completed', 'Pending'][rand(0, 3)],
                            ];
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'data' => $enrollments
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch enrollments'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching enrollments: ' . $e->getMessage()
            ], 500);
        }
    }

    private function parseEnrolledCourses($field)
    {
        $courses = [];
        if (isset($field['arrayValue'])) {
            $values = $field['arrayValue']['values'] ?? [];
            foreach ($values as $value) {
                if (isset($value['stringValue'])) {
                    $courses[] = $value['stringValue'];
                }
            }
        }
        return $courses;
    }

    private function parseEnrolledUsers($field)
    {
        $users = [];
        if (isset($field['arrayValue'])) {
            $values = $field['arrayValue']['values'] ?? [];
            foreach ($values as $value) {
                if (isset($value['stringValue'])) {
                    $users[] = $value['stringValue'];
                }
            }
        }
        return $users;
    }
}
