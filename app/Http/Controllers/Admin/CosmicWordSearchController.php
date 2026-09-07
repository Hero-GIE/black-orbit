<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CosmicWordSearchController extends Controller
{
    private function getFirebaseToken()
    {
        $token = session('firebase_token');
        if (empty($token)) {
            return null;
        }
        return $token;
    }

    public function index()
    {
        return view('admin.cosmic.index');
    }

    public function fetchProgress()
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

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get(
                "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/cosmic_word_search_progress"
            );

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch progress data'
                ], 500);
            }

            $data = $response->json();
            $documents = $data['documents'] ?? [];

            $progressData = [];
            $totalStars = 0;
            $totalCompletedLevels = 0;
            $totalLevels = 0;

            foreach ($documents as $doc) {
                $fields = $doc['fields'] ?? [];
                $userId = basename($doc['name']);

                $userInfo = $this->getUserInfo($userId);

                $levels = [];
                $userTotalStars = 0;
                $userCompletedLevels = 0;
                $userTotalLevels = 0;

                if (isset($fields['levels']) && isset($fields['levels']['mapValue'])) {
                    $levelFields = $fields['levels']['mapValue']['fields'] ?? [];

                    foreach ($levelFields as $key => $value) {
                        if (is_numeric($key) || strpos($key, 'level') !== false) {
                            if (!isset($value['mapValue'])) {
                                continue;
                            }

                            $levelData = $value['mapValue']['fields'] ?? [];

                            if (isset($levelData['levelId'])) {
                                $levelId = intval($levelData['levelId']['integerValue'] ?? 0);
                                $completed = $levelData['completed']['booleanValue'] ?? false;
                                $stars = intval($levelData['stars']['integerValue'] ?? 0);
                                $bestTime = intval($levelData['bestTimeSeconds']['integerValue'] ?? 0);
                                $timesPlayed = intval($levelData['timesPlayed']['integerValue'] ?? 0);

                                if ($completed) {
                                    $userCompletedLevels++;
                                    $totalCompletedLevels++;
                                }
                                $userTotalLevels++;
                                $totalLevels++;
                                $userTotalStars += $stars;
                                $totalStars += $stars;

                                $levels[] = [
                                    'levelId' => $levelId,
                                    'completed' => $completed,
                                    'stars' => $stars,
                                    'bestTimeSeconds' => $bestTime,
                                    'timesPlayed' => $timesPlayed,
                                ];
                            }
                        }
                    }

                    usort($levels, function($a, $b) {
                        return $a['levelId'] - $b['levelId'];
                    });
                }

                if (isset($fields['totalStars']) && isset($fields['totalStars']['integerValue'])) {
                    $userTotalStars = intval($fields['totalStars']['integerValue']);
                }

                if ($userTotalLevels > 0 || $userTotalStars > 0) {
                    $progressData[] = [
                        'user_id' => $userId,
                        'username' => $userInfo['username'] ?? 'Unknown User',
                        'email' => $userInfo['email'] ?? '',
                        'totalStars' => $userTotalStars,
                        'completedLevels' => $userCompletedLevels,
                        'totalLevels' => $userTotalLevels,
                        'levels' => $levels,
                        'hasProgress' => true,
                    ];
                }
            }

            $totalWords = 100;
            $totalPossibleStars = $totalLevels * 3;
            $completionPercentage = $totalLevels > 0 ? round(($totalCompletedLevels / $totalLevels) * 100) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $progressData,
                    'stats' => [
                        'total_users' => count($progressData),
                        'total_stars' => $totalStars,
                        'total_completed_levels' => $totalCompletedLevels,
                        'total_levels' => $totalLevels,
                        'completion_percentage' => $completionPercentage,
                        'stars_percentage' => $totalPossibleStars > 0 ? round(($totalStars / $totalPossibleStars) * 100) : 0,
                        'total_words' => $totalWords,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching progress: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getUserInfo($userId)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return ['username' => 'Unknown', 'email' => ''];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get(
                "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$userId}"
            );

            if ($response->successful()) {
                $data = $response->json();
                $fields = $data['fields'] ?? [];
                return [
                    'username' => $fields['username']['stringValue'] ?? 'Unknown',
                    'email' => $fields['email']['stringValue'] ?? '',
                ];
            }
        } catch (\Exception $e) {
        }
        return ['username' => 'Unknown', 'email' => ''];
    }

    public function getUserProgress($userId)
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

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get(
                "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/cosmic_word_search_progress/{$userId}"
            );

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No progress found for this user'
                ], 404);
            }

            $data = $response->json();
            $fields = $data['fields'] ?? [];

            $userInfo = $this->getUserInfo($userId);

            $levels = [];
            $totalStars = 0;
            $completedLevels = 0;

            if (isset($fields['levels']) && isset($fields['levels']['mapValue'])) {
                $levelFields = $fields['levels']['mapValue']['fields'] ?? [];

                foreach ($levelFields as $key => $value) {
                    if (is_numeric($key) || strpos($key, 'level') !== false) {
                        if (!isset($value['mapValue'])) {
                            continue;
                        }

                        $levelData = $value['mapValue']['fields'] ?? [];

                        if (isset($levelData['levelId'])) {
                            $levelId = intval($levelData['levelId']['integerValue'] ?? 0);
                            $completed = $levelData['completed']['booleanValue'] ?? false;
                            $stars = intval($levelData['stars']['integerValue'] ?? 0);
                            $bestTime = intval($levelData['bestTimeSeconds']['integerValue'] ?? 0);
                            $timesPlayed = intval($levelData['timesPlayed']['integerValue'] ?? 0);

                            if ($completed) {
                                $completedLevels++;
                            }
                            $totalStars += $stars;

                            $levels[] = [
                                'levelId' => $levelId,
                                'completed' => $completed,
                                'stars' => $stars,
                                'bestTimeSeconds' => $bestTime,
                                'timesPlayed' => $timesPlayed,
                            ];
                        }
                    }
                }
            }

            if (isset($fields['totalStars']) && isset($fields['totalStars']['integerValue'])) {
                $totalStars = intval($fields['totalStars']['integerValue']);
            }

            usort($levels, function($a, $b) {
                return $a['levelId'] - $b['levelId'];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'username' => $userInfo['username'] ?? 'Unknown User',
                    'email' => $userInfo['email'] ?? '',
                    'totalStars' => $totalStars,
                    'completedLevels' => $completedLevels,
                    'totalLevels' => count($levels),
                    'levels' => $levels,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching user progress: ' . $e->getMessage()
            ], 500);
        }
    }
}
