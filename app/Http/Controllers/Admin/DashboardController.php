<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    /**
     * Get Firebase ID token from session
     */
    private function getFirebaseToken()
    {
        return session('firebase_token');
    }

    public function index()
    {
        return view('dashboard');
    }

    /**
     * Get dashboard stats
     */
    public function getStats()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['code' => 401, 'message' => 'Not authenticated'], 401);
            }

            $users = $this->getAllUsers($token, $projectId);
            $courses = $this->getAllCourses($token, $projectId);
            $chats = $this->getAllChats($token, $projectId);
            $cosmicProgress = $this->getCosmicProgress($token, $projectId);
            $enrollments = $this->getTotalEnrollments($courses);

            return response()->json([
                'code' => 200,
                'data' => [
                    'chats' => [
                        'total' => count($chats),
                        'today' => $this->getTodayCount($chats),
                    ],
                    'wordSearch' => $cosmicProgress,
                    'courses' => [
                        'total' => count($courses),
                        'today' => $this->getTodayCount($courses),
                    ],
                    'enrollments' => [
                        'total' => $enrollments,
                        'today' => 0,
                    ],
                    'users' => [
                        'total' => count($users),
                        'today' => $this->getTodayCount($users),
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => 'Error fetching stats'], 500);
        }
    }

    /**
     * Get real activity logs from Firestore
     */
    public function getActivity()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['code' => 401, 'message' => 'Not authenticated'], 401);
            }

            $activities = [];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get(
                "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/activity_logs",
                [
                    'orderBy' => 'createdAt desc',
                    'pageSize' => 15,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $documents = $data['documents'] ?? [];

                foreach ($documents as $doc) {
                    $fields = $doc['fields'] ?? [];
                    $type = $fields['type']['stringValue'] ?? 'general';

                    $metadata = [];
                    if (isset($fields['metadata']['mapValue']['fields'])) {
                        foreach ($fields['metadata']['mapValue']['fields'] as $key => $val) {
                            $metadata[$key] = $val['stringValue'] ?? $val['integerValue'] ?? $val['booleanValue'] ?? null;
                        }
                    }

                    $activities[] = [
                        'id' => $doc['name'] ?? '',
                        'type' => $type,
                        'typeInfo' => ActivityLogger::getTypeInfo($type),
                        'title' => $fields['title']['stringValue'] ?? '',
                        'description' => $fields['description']['stringValue'] ?? '',
                        'username' => $fields['username']['stringValue'] ?? 'System',
                        'email' => $fields['email']['stringValue'] ?? '',
                        'userId' => $fields['userId']['stringValue'] ?? '',
                        'metadata' => $metadata,
                        'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                        'timeAgo' => $this->timeAgo($fields['createdAt']['timestampValue'] ?? ''),
                    ];
                }
            }

            if (empty($activities)) {
                $activities = $this->getDerivedActivities($token, $projectId);
            }

            return response()->json(['code' => 200, 'data' => $activities]);

        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => 'Error fetching activity'], 500);
        }
    }

    public function getAnalytics(Request $request)
    {
        try {
            $days = $request->get('days', 30);
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['code' => 401, 'message' => 'Not authenticated'], 401);
            }

            $users = $this->getAllUsers($token, $projectId);
            $courses = $this->getAllCourses($token, $projectId);
            $chats = $this->getAllChats($token, $projectId);

            $dateMap = [];

            foreach ($users as $user) {
                if (isset($user['createdAt'])) {
                    $date = date('Y-m-d', strtotime($user['createdAt']));
                    if (!isset($dateMap[$date])) $dateMap[$date] = ['users' => 0, 'courses' => 0, 'chats' => 0];
                    $dateMap[$date]['users']++;
                }
            }

            foreach ($courses as $course) {
                if (isset($course['createdAt'])) {
                    $date = date('Y-m-d', strtotime($course['createdAt']));
                    if (!isset($dateMap[$date])) $dateMap[$date] = ['users' => 0, 'courses' => 0, 'chats' => 0];
                    $dateMap[$date]['courses']++;
                }
            }

            foreach ($chats as $chat) {
                if (isset($chat['createdAt'])) {
                    $date = date('Y-m-d', strtotime($chat['createdAt']));
                    if (!isset($dateMap[$date])) $dateMap[$date] = ['users' => 0, 'courses' => 0, 'chats' => 0];
                    $dateMap[$date]['chats']++;
                }
            }

            $labels = [];
            $chatsData = [];
            $coursesData = [];
            $usersData = [];

            $startDate = new \DateTime();
            $startDate->modify("-{$days} days");
            $endDate = new \DateTime();

            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                $dateKey = $currentDate->format('Y-m-d');
                $labels[] = $dateKey;
                $chatsData[] = $dateMap[$dateKey]['chats'] ?? 0;
                $coursesData[] = $dateMap[$dateKey]['courses'] ?? 0;
                $usersData[] = $dateMap[$dateKey]['users'] ?? 0;
                $currentDate->modify('+1 day');
            }

            return response()->json([
                'code' => 200,
                'data' => [
                    'labels' => $labels,
                    'chats' => $chatsData,
                    'courses' => $coursesData,
                    'users' => $usersData,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => 'Error fetching analytics'], 500);
        }
    }

    public function getDistribution()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['code' => 401, 'message' => 'Not authenticated'], 401);
            }

            $chats = $this->getAllChats($token, $projectId);
            $courses = $this->getAllCourses($token, $projectId);
            $users = $this->getAllUsers($token, $projectId);

            return response()->json([
                'code' => 200,
                'data' => [
                    'chats' => count($chats),
                    'courses' => count($courses),
                    'users' => count($users),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => 'Error fetching distribution'], 500);
        }
    }

    private function getDerivedActivities($token, $projectId)
    {
        $activities = [];

        $usersResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
            ->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users", ['orderBy' => 'createdAt desc', 'pageSize' => 5]);

        if ($usersResponse->successful()) {
            foreach ($usersResponse->json()['documents'] ?? [] as $doc) {
                $fields = $doc['fields'] ?? [];
                $createdAt = $fields['createdAt']['timestampValue'] ?? '';
                $activities[] = [
                    'id' => $doc['name'] ?? '',
                    'type' => 'user_registered',
                    'typeInfo' => ActivityLogger::getTypeInfo('user_registered'),
                    'title' => 'New user registered',
                    'description' => ($fields['email']['stringValue'] ?? '') . ' joined the platform',
                    'username' => $fields['username']['stringValue'] ?? 'Unknown',
                    'email' => $fields['email']['stringValue'] ?? '',
                    'userId' => $fields['uid']['stringValue'] ?? '',
                    'metadata' => ['interest' => $fields['interest']['stringValue'] ?? ''],
                    'createdAt' => $createdAt,
                    'timeAgo' => $this->timeAgo($createdAt),
                ];
            }
        }

        $coursesResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
            ->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses", ['orderBy' => 'createdAt desc', 'pageSize' => 5]);

        if ($coursesResponse->successful()) {
            foreach ($coursesResponse->json()['documents'] ?? [] as $doc) {
                $fields = $doc['fields'] ?? [];
                $createdAt = $fields['createdAt']['timestampValue'] ?? '';
                $enrolledCount = count($fields['enrolledUsers']['arrayValue']['values'] ?? []);
                $activities[] = [
                    'id' => $doc['name'] ?? '',
                    'type' => 'course_created',
                    'typeInfo' => ActivityLogger::getTypeInfo('course_created'),
                    'title' => 'Course created: ' . ($fields['coursename']['stringValue'] ?? 'Untitled'),
                    'description' => $enrolledCount . ' student(s) enrolled',
                    'username' => 'Admin',
                    'email' => 'admin@gmail.com',
                    'userId' => 'admin',
                    'metadata' => ['category' => $fields['category']['stringValue'] ?? ''],
                    'createdAt' => $createdAt,
                    'timeAgo' => $this->timeAgo($createdAt),
                ];
            }
        }

        $chatsResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
            ->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/Chats", ['orderBy' => 'createdAt desc', 'pageSize' => 5]);

        if (!$chatsResponse->successful()) {
            $chatsResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/chats", ['orderBy' => 'createdAt desc', 'pageSize' => 5]);
        }

        if ($chatsResponse->successful()) {
            foreach ($chatsResponse->json()['documents'] ?? [] as $doc) {
                $fields = $doc['fields'] ?? [];
                $createdAt = $fields['createdAt']['timestampValue'] ?? '';
                $userCount = count($fields['users']['arrayValue']['values'] ?? []);
                $isGroup = $fields['isGroup']['booleanValue'] ?? false;
                $activities[] = [
                    'id' => $doc['name'] ?? '',
                    'type' => 'chat_created',
                    'typeInfo' => ActivityLogger::getTypeInfo('chat_created'),
                    'title' => $isGroup ? 'New group chat created' : 'New direct message',
                    'description' => $userCount . ' participant(s)',
                    'username' => 'Chat System',
                    'email' => '',
                    'userId' => '',
                    'metadata' => ['isGroup' => $isGroup],
                    'createdAt' => $createdAt,
                    'timeAgo' => $this->timeAgo($createdAt),
                ];
            }
        }

        usort($activities, function ($a, $b) {
            return strtotime($b['createdAt']) - strtotime($a['createdAt']);
        });

        return array_slice($activities, 0, 10);
    }

    private function timeAgo(string $timestamp): string
    {
        if (empty($timestamp)) return 'Unknown';
        try {
            $past = new \DateTime($timestamp);
            $now = new \DateTime();
            $diff = $now->getTimestamp() - $past->getTimestamp();

            if ($diff < 60) return 'Just now';
            if ($diff < 3600) return floor($diff / 60) . 'm ago';
            if ($diff < 86400) return floor($diff / 3600) . 'h ago';
            if ($diff < 604800) return floor($diff / 86400) . 'd ago';
            if ($diff < 2592000) return floor($diff / 604800) . 'w ago';
            return $past->format('M j, Y');
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getAllUsers($token, $projectId) {
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users");
            if ($response->successful()) {
                $users = [];
                foreach ($response->json()['documents'] ?? [] as $doc) {
                    $f = $doc['fields'] ?? [];
                    $users[] = [
                        'uid' => $f['uid']['stringValue'] ?? '',
                        'username' => $f['username']['stringValue'] ?? '',
                        'email' => $f['email']['stringValue'] ?? '',
                        'role' => $f['role']['stringValue'] ?? 'student',
                        'accesslevel' => $f['accesslevel']['stringValue'] ?? 'user',
                        'interest' => $f['interest']['stringValue'] ?? '',
                        'institution' => $f['institution']['stringValue'] ?? '',
                        'enrolledCourses' => $f['enrolledCourses']['arrayValue']['values'] ?? [],
                        'createdAt' => $f['createdAt']['timestampValue'] ?? '',
                        'updatedAt' => $f['updatedAt']['timestampValue'] ?? '',
                    ];
                }
                return $users;
            }
            return [];
        } catch (\Exception $e) { return []; }
    }

    private function getAllCourses($token, $projectId) {
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/courses");
            if ($response->successful()) {
                $courses = [];
                foreach ($response->json()['documents'] ?? [] as $doc) {
                    $f = $doc['fields'] ?? [];
                    $courses[] = [
                        'courseid' => $f['courseid']['stringValue'] ?? '',
                        'coursename' => $f['coursename']['stringValue'] ?? '',
                        'category' => $f['category']['stringValue'] ?? '',
                        'courseLevel' => $f['courseLevel']['stringValue'] ?? '',
                        'image' => $f['image']['stringValue'] ?? '',
                        'lessoncount' => $f['lessoncount']['integerValue'] ?? 0,
                        'enrolledUsers' => $f['enrolledUsers']['arrayValue']['values'] ?? [],
                        'createdAt' => $f['createdAt']['timestampValue'] ?? '',
                    ];
                }
                return $courses;
            }
            return [];
        } catch (\Exception $e) { return []; }
    }

    private function getAllChats($token, $projectId) {
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/Chats");
            if (!$response->successful()) {
                $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/chats");
            }
            if ($response->successful()) {
                $chats = [];
                foreach ($response->json()['documents'] ?? [] as $doc) {
                    $f = $doc['fields'] ?? [];
                    $chats[] = [
                        'chatid' => $f['chatid']['stringValue'] ?? '',
                        'isGroup' => $f['isGroup']['booleanValue'] ?? false,
                        'users' => $f['users']['arrayValue']['values'] ?? [],
                        'createdAt' => $f['createdAt']['timestampValue'] ?? '',
                        'updatedAt' => $f['updatedAt']['timestampValue'] ?? '',
                    ];
                }
                return $chats;
            }
            return [];
        } catch (\Exception $e) { return []; }
    }

    private function getCosmicProgress($token, $projectId) {
        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/cosmic_word_search_progress");
            if (!$response->successful()) {
                return ['found' => 0, 'total' => 100, 'percentage' => 0, 'stars' => 0, 'completed_levels' => 0, 'total_levels' => 0];
            }
            $totalStars = 0; $totalCompletedLevels = 0; $totalLevels = 0;
            foreach ($response->json()['documents'] ?? [] as $doc) {
                $f = $doc['fields'] ?? [];
                if (isset($f['totalStars']['integerValue'])) $totalStars += intval($f['totalStars']['integerValue']);
                if (isset($f['levels']['mapValue']['fields'])) {
                    foreach ($f['levels']['mapValue']['fields'] as $value) {
                        if (!isset($value['mapValue'])) continue;
                        $levelData = $value['mapValue']['fields'] ?? [];
                        if (isset($levelData['levelId'])) {
                            $totalLevels++;
                            if (!empty($levelData['completed']['booleanValue'])) $totalCompletedLevels++;
                        }
                    }
                }
            }
            return [
                'found' => $totalCompletedLevels * 5, 'total' => 100,
                'percentage' => $totalLevels > 0 ? round(($totalCompletedLevels / $totalLevels) * 100) : 0,
                'stars' => $totalStars, 'completed_levels' => $totalCompletedLevels, 'total_levels' => $totalLevels,
            ];
        } catch (\Exception $e) {
            return ['found' => 0, 'total' => 100, 'percentage' => 0, 'stars' => 0, 'completed_levels' => 0, 'total_levels' => 0];
        }
    }

    private function getTotalEnrollments($courses) {
        $total = 0;
        foreach ($courses as $course) $total += count($course['enrolledUsers'] ?? []);
        return $total;
    }

    private function getTodayCount($items) {
        $today = date('Y-m-d'); $count = 0;
        foreach ($items as $item) {
            if (isset($item['createdAt']) && date('Y-m-d', strtotime($item['createdAt'])) === $today) $count++;
        }
        return $count;
    }
}
