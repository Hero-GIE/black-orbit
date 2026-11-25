<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class UserActivityController extends Controller
{
    public function index()
    {
        return view('admin.user-activities.index');
    }

    private function getFirebaseToken()
    {
        $token = session('firebase_token');
        if (empty($token)) {
            return null;
        }
        return $token;
    }

    private function projectId(): string
    {
        return (string) env('FIREBASE_PROJECT_ID', '');
    }

    /**
     * Safely extract an integer from a Firestore value object.
     * Handles integerValue, doubleValue, stringValue, and nullValue.
     * This is critical because Firestore can return the same numeric field
     * in different shapes depending on how it was written.
     */
    private function intFromFirestore(?array $field): int
    {
        if (!$field) return 0;
        if (isset($field['integerValue'])) return (int) $field['integerValue'];
        if (isset($field['doubleValue']))  return (int) round((float) $field['doubleValue']);
        if (isset($field['stringValue']))  return (int) $field['stringValue'];
        return 0;
    }

    public function fetchReport(Request $request)
    {
        try {
            $projectId = $this->projectId();
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase',
                ], 401);
            }

            $search  = trim((string) $request->query('q', ''));
            $role    = trim((string) $request->query('role', ''));
            $sortBy  = trim((string) $request->query('sort', 'engagement'));
            $sortDir = strtolower(trim((string) $request->query('dir', 'desc'))) === 'asc' ? 'asc' : 'desc';

            Log::info('[fetchReport] params', [
                'search'   => $search,
                'role'     => $role,
                'sort_by'  => $sortBy,
                'sort_dir' => $sortDir,
            ]);

            $snapshot = $this->getCachedSnapshot($projectId, $token);

            $activities       = $snapshot['activities'];
            $usersMap         = $snapshot['users'];
            $cosmicMap        = $snapshot['cosmic'];
            $playersMap       = $snapshot['players'];
            $enrollmentCounts = $snapshot['enrollments'];
            $lastActivityMap  = $snapshot['lastActivity'];
            $overTime         = $snapshot['overTime'];
            $videoViews       = $snapshot['videoViews'];
            $articleViews     = $snapshot['articleViews'];
            $progressMap      = $snapshot['progress'] ?? [];

            $rows = [];
            $totals = [
                'articlesRead'      => 0,
                'factsRead'         => 0,
                'videosWatched'     => 0,
                'gamesCompleted'    => 0,
                'personalityview'   => 0,
                'quizzesCompleted'  => 0,
                'cosmicStars'       => 0,
                'spaceXp'           => 0,
                'courseEnrollments' => 0,
                'completedLessons'  => 0,
                'completedQuizzes'  => 0,
                'completedCourses'  => 0,
            ];
            $byRole = [];
            $activeUserCount = 0;

            foreach ($activities as $doc) {
                $fields = $doc['fields'] ?? [];

                // ── Resolve userId with all fallbacks ──
                //   1. userid.stringValue (normal case)
                //   2. uid.stringValue (alternate schema)
                //   3. Firestore doc ID (always present)
                $userId = '';
                if (!empty($fields['userid']['stringValue'])) {
                    $userId = $fields['userid']['stringValue'];
                } elseif (!empty($fields['uid']['stringValue'])) {
                    $userId = $fields['uid']['stringValue'];
                } else {
                    $userId = basename($doc['name'] ?? '');
                }
                if ($userId === '') continue;

                $user   = $usersMap[$userId] ?? null;
                $cosmic = $cosmicMap[$userId] ?? ['stars' => 0, 'completed_levels' => 0, 'total_levels' => 0];
                $player = $playersMap[$userId] ?? ['phase' => '—', 'xp' => 0, 'achievements' => 0];
                $enrolled = $enrollmentCounts[$userId] ?? 0;
                $lastActivity = $lastActivityMap[$userId] ?? null;

                // ── Safely extract all counters regardless of Firestore shape ──
                $articlesRead     = $this->intFromFirestore($fields['articlesRead']     ?? null);
                $factsRead        = $this->intFromFirestore($fields['factsRead']        ?? null);
                $videosWatched    = $this->intFromFirestore($fields['videosWatched']    ?? null);
                $gamesCompleted   = $this->intFromFirestore($fields['gamesCompleted']   ?? null);
                $personalityView  = $this->intFromFirestore($fields['personalityview']  ?? null);
                $quizzesCompleted = $this->intFromFirestore($fields['quizzesCompleted'] ?? null);

                $engagementScore = $articlesRead + $factsRead + $videosWatched
                    + $gamesCompleted + $personalityView + $quizzesCompleted
                    + $cosmic['stars'] + (int) floor($player['xp'] / 100);

                $userProgress = $progressMap[$userId] ?? [
                    'total_completed_lessons' => 0,
                    'total_completed_quizzes' => 0,
                    'completed_courses'       => 0,
                    'courses'                 => [],
                ];

                $row = [
                    'user_id'          => $userId,
                    'username'         => $user['username']    ?? 'Unknown',
                    'email'            => $user['email']       ?? '',
                    'role'             => $user['role']        ?? 'student',
                    'institution'      => $user['institution'] ?? '',
                    'activities' => [
                        'articlesRead'     => $articlesRead,
                        'factsRead'        => $factsRead,
                        'videosWatched'    => $videosWatched,
                        'gamesCompleted'   => $gamesCompleted,
                        'personalityview'  => $personalityView,
                        'quizzesCompleted' => $quizzesCompleted,
                    ],
                    'cosmic' => $cosmic,
                    'space'  => $player,
                    'progress' => $userProgress,
                    'courses_enrolled' => $enrolled,
                    'last_activity'    => $lastActivity,
                    'engagement_score' => $engagementScore,
                ];

                if ($engagementScore === 0 && $enrolled === 0
                    && ($userProgress['total_completed_lessons'] ?? 0) === 0) continue;
                $activeUserCount++;

                if ($search !== '') {
                    $haystack = strtolower($row['username'] . ' ' . $row['email'] . ' ' . $row['institution']);
                    if (!str_contains($haystack, strtolower($search))) continue;
                }

                if ($role !== '' && $role !== 'all' && $row['role'] !== $role) continue;

                $rows[] = $row;

                $totals['articlesRead']      += $articlesRead;
                $totals['factsRead']          += $factsRead;
                $totals['videosWatched']      += $videosWatched;
                $totals['gamesCompleted']     += $gamesCompleted;
                $totals['personalityview']    += $personalityView;
                $totals['quizzesCompleted']   += $quizzesCompleted;
                $totals['cosmicStars']        += $cosmic['stars'];
                $totals['spaceXp']            += $player['xp'];
                $totals['courseEnrollments']  += $enrolled;
                $totals['completedLessons']   += $userProgress['total_completed_lessons'];
                $totals['completedQuizzes']   += $userProgress['total_completed_quizzes'];
                $totals['completedCourses']   += $userProgress['completed_courses'];

                $r = $row['role'];
                if (!isset($byRole[$r])) {
                    $byRole[$r] = [
                        'articlesRead' => 0, 'factsRead' => 0, 'videosWatched' => 0,
                        'gamesCompleted' => 0, 'personalityview' => 0, 'quizzesCompleted' => 0,
                        'users' => 0,
                    ];
                }
                $byRole[$r]['articlesRead']      += $articlesRead;
                $byRole[$r]['factsRead']         += $factsRead;
                $byRole[$r]['videosWatched']     += $videosWatched;
                $byRole[$r]['gamesCompleted']    += $gamesCompleted;
                $byRole[$r]['personalityview']   += $personalityView;
                $byRole[$r]['quizzesCompleted']  += $quizzesCompleted;
                $byRole[$r]['users']++;
            }

            // ── Sort with proper type handling ──
            usort($rows, function ($a, $b) use ($sortBy, $sortDir) {
                $av = $this->sortValue($a, $sortBy);
                $bv = $this->sortValue($b, $sortBy);

                if (is_string($av) && is_string($bv)) {
                    $cmp = strcasecmp($av, $bv);
                } else {
                    $cmp = $av <=> $bv;
                }

                // Stable tie-breaker
                if ($cmp === 0) {
                    $cmp = ($b['engagement_score'] ?? 0) <=> ($a['engagement_score'] ?? 0);
                }
                if ($cmp === 0) {
                    $cmp = strcasecmp($a['username'] ?? '', $b['username'] ?? '');
                }

                return $sortDir === 'asc' ? $cmp : -$cmp;
            });

            Log::info('[fetchReport] sorted', [
                'sort_by'   => $sortBy,
                'sort_dir'  => $sortDir,
                'row_count' => count($rows),
                'first_5'   => array_map(fn($r) => [
                    'username' => $r['username'],
                    'value'    => $this->sortValue($r, $sortBy),
                ], array_slice($rows, 0, 5)),
            ]);

            $topUsers = array_slice(array_map(fn($r) => [
                'username'         => $r['username'],
                'role'             => $r['role'],
                'engagement_score' => $r['engagement_score'],
            ], $rows), 0, 10);

$engagementByType = [
    ['label' => 'Articles Read',        'value' => $articleViews['total_views'],  'color' => '#8ab4ff'],
    ['label' => 'Facts Read',           'value' => $totals['factsRead'],          'color' => '#75c594'],
    ['label' => 'Videos Watched',       'value' => $videoViews['total_views'],    'color' => '#ffcc66'],
    ['label' => 'Games Completed',      'value' => $totals['gamesCompleted'],     'color' => '#f69595'],
    ['label' => 'Personalities Viewed', 'value' => $totals['personalityview'],    'color' => '#b0b7bd'],
    ['label' => 'Quizzes Completed',    'value' => $totals['quizzesCompleted'],   'color' => '#96e6c9'],
];

            $engagementByRole = [];
            foreach ($byRole as $roleName => $counts) {
                $total = $counts['articlesRead'] + $counts['factsRead'] + $counts['videosWatched']
                       + $counts['gamesCompleted'] + $counts['personalityview'] + $counts['quizzesCompleted'];
                $engagementByRole[] = ['role' => $roleName, 'users' => $counts['users'], 'total' => $total];
            }
            usort($engagementByRole, fn($a, $b) => $b['total'] <=> $a['total']);

            return response()->json([
                'success' => true,
                'data' => [
                    'rows'               => $rows,
                    'totals'             => $totals,
                    'active_user_count'  => $activeUserCount,
                    'top_users'          => $topUsers,
                    'engagement_by_type' => $engagementByType,
                    'engagement_by_role' => $engagementByRole,
                    'over_time'          => $overTime,
                    'video_views'        => [
                        'total'      => $videoViews['total_views'],
                        'top_video'  => $videoViews['top_video'],
                        'top_videos' => array_slice($videoViews['videos'], 0, 4),
                        'videos'     => $videoViews['videos'],
                    ],
                    'article_views' => [
                        'total'        => $articleViews['total_views'],
                        'top_article'  => $articleViews['top_article'],
                        'top_articles' => array_slice($articleViews['articles'], 0, 4),
                        'articles'     => $articleViews['articles'],
                    ],
                    'progress_summary' => [
                        'total_completed_lessons' => $totals['completedLessons'],
                        'total_completed_quizzes' => $totals['completedQuizzes'],
                        'total_completed_courses' => $totals['completedCourses'],
                        'users_with_progress'     => count(array_filter($progressMap, fn($p) => !empty($p['courses']))),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('UserActivity report failed', [
                'msg'   => $e->getMessage(),
                'class' => get_class($e),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error building report: ' . $e->getMessage(),
                'type'    => get_class($e),
            ], 500);
        }
    }

    private function getCachedSnapshot(string $projectId, string $token): array
    {
        $cacheKey = 'user_activity_snapshot:' . sha1($projectId);
        $ttl      = app()->environment('local') ? 30 : 300;

        return Cache::remember($cacheKey, now()->addSeconds($ttl), function () use ($projectId, $token) {
            $activities   = $this->fetchAllDocuments($projectId, $token, 'user_activities');
            $users        = $this->fetchUsersMap($projectId, $token);
            $cosmic       = $this->fetchCosmicMap($projectId, $token);
            $players      = $this->fetchPlayersMap($projectId, $token);
            $enrollments  = $this->fetchEnrollmentCounts($projectId, $token);
            $videoViews   = $this->fetchVideoViews($projectId, $token);
            $articleViews = $this->fetchArticleViews($projectId, $token);
            $progress     = $this->fetchProgressMap($projectId, $token);

            [$lastActivity, $overTime] = $this->fetchActivityLogStats($projectId, $token);

            return [
                'activities'   => $activities,
                'users'        => $users,
                'cosmic'       => $cosmic,
                'players'      => $players,
                'enrollments'  => $enrollments,
                'lastActivity' => $lastActivity,
                'overTime'     => $overTime,
                'videoViews'   => $videoViews,
                'articleViews' => $articleViews,
                'progress'     => $progress,
            ];
        });
    }

    /**
     * Return the value used for sorting. Type is mixed because username
     * sort returns a string, all numeric fields return int.
     */
    private function sortValue(array $row, string $sortBy): mixed
    {
        return match ($sortBy) {
            'articles'      => (int) ($row['activities']['articlesRead'] ?? 0),
            'facts'         => (int) ($row['activities']['factsRead'] ?? 0),
            'videos'        => (int) ($row['activities']['videosWatched'] ?? 0),
            'games'         => (int) ($row['activities']['gamesCompleted'] ?? 0),
            'personalities' => (int) ($row['activities']['personalityview'] ?? 0),
            'quizzes'       => (int) ($row['activities']['quizzesCompleted'] ?? 0),
            'stars'         => (int) ($row['cosmic']['stars'] ?? 0),
            'xp'            => (int) ($row['space']['xp'] ?? 0),
            'courses'       => (int) ($row['courses_enrolled'] ?? 0),
            'lessons'       => (int) ($row['progress']['total_completed_lessons'] ?? 0),
            'coursesdone'   => (int) ($row['progress']['completed_courses'] ?? 0),
            'username'      => (string) ($row['username'] ?? ''),
            default         => (int) ($row['engagement_score'] ?? 0),
        };
    }

    private function fetchAllDocuments(string $projectId, string $token, string $collection): array
    {
        $baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$collection}";
        $docs    = [];
        $pageToken = null;

        for ($page = 0; $page < 50; $page++) {
            $query = ['pageSize' => 300];
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }
            $url = $baseUrl . '?' . http_build_query($query);

            $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->timeout(60)
                ->get($url);

            if (!$resp->successful()) {
                Log::error('[fetchAllDocuments] Firestore fetch failed', [
                    'collection' => $collection,
                    'status'     => $resp->status(),
                    'body'       => substr($resp->body(), 0, 500),
                    'page'       => $page,
                ]);
                break;
            }

            $data = $resp->json();
            foreach ($data['documents'] ?? [] as $doc) {
                $docs[] = $doc;
            }

            $pageToken = $data['nextPageToken'] ?? null;
            if (!$pageToken) {
                break;
            }
        }

        return $docs;
    }

    private function fetchUsersMap(string $projectId, string $token): array
    {
        $docs = $this->fetchAllDocuments($projectId, $token, 'users');
        $map  = [];

        foreach ($docs as $doc) {
            $fields = $doc['fields'] ?? [];
            $uid = $fields['uid']['stringValue'] ?? basename($doc['name'] ?? '');
            if (empty($uid)) continue;
            $map[$uid] = [
                'username'    => $fields['username']['stringValue']    ?? 'Unknown',
                'email'       => $fields['email']['stringValue']       ?? '',
                'role'        => $fields['role']['stringValue']        ?? 'student',
                'institution' => $fields['institution']['stringValue'] ?? '',
            ];
        }

        return $map;
    }

    private function fetchArticleViews(string $projectId, string $token): array
    {
        $articles   = [];
        $totalViews = 0;

        $docs = $this->fetchAllDocuments($projectId, $token, 'articles');

        Log::info('[fetchArticleViews] docs fetched', ['count' => count($docs)]);

        foreach ($docs as $doc) {
            $fields    = $doc['fields'] ?? [];
            $articleId = basename($doc['name'] ?? '');
            if (empty($articleId)) continue;

            $views = $this->intFromFirestore($fields['viewcount'] ?? null);

            $articles[] = [
                'article_id'   => $articleId,
                'title'        => $fields['title']['stringValue']    ?? 'Untitled article',
                'slug'         => $fields['slug']['stringValue']     ?? $articleId,
                'category'     => $fields['category']['stringValue'] ?? '',
                'author'       => $fields['author']['stringValue']   ?? '',
                'image'        => $fields['image']['stringValue']    ?? '',
                'published_at' => $fields['publishedAt']['timestampValue']
                               ?? $fields['createdAt']['timestampValue']
                               ?? '',
                'views'        => $views,
            ];

            $totalViews += $views;
        }

        Log::info('[fetchArticleViews] summary', [
            'article_count' => count($articles),
            'total_views'   => $totalViews,
            'top_article'   => $articles[0]['title'] ?? null,
        ]);

        usort($articles, fn($a, $b) => $b['views'] <=> $a['views']);

        return [
            'articles'    => $articles,
            'total_views' => $totalViews,
            'top_article' => $articles[0] ?? null,
        ];
    }

    private function fetchProgressMap(string $projectId, string $token): array
    {
        $map = [];

        $progressDocs = $this->fetchAllDocuments($projectId, $token, 'progress');

        Log::info('[fetchProgressMap] user progress docs', ['count' => count($progressDocs)]);

        foreach ($progressDocs as $progressDoc) {
            $userId = basename($progressDoc['name'] ?? '');
            if (empty($userId)) continue;

            $subDocs = $this->fetchAllDocuments($projectId, $token, "progress/{$userId}/courses");

            $totalLessons = 0;
            $totalQuizzes = 0;
            $completedCourses = 0;
            $courses = [];

            foreach ($subDocs as $doc) {
                $fields = $doc['fields'] ?? [];
                $courseId = basename($doc['name'] ?? '');
                if (empty($courseId)) continue;

                $completedLessons = [];
                foreach ($fields['completedLessons']['arrayValue']['values'] ?? [] as $v) {
                    if (isset($v['stringValue'])) $completedLessons[] = $v['stringValue'];
                }

                $completedQuizzes = [];
                foreach ($fields['completedQuizzes']['arrayValue']['values'] ?? [] as $v) {
                    if (isset($v['stringValue'])) $completedQuizzes[] = $v['stringValue'];
                }

                $isCompleted = (bool) ($fields['completed']['booleanValue'] ?? false);

                $courses[] = [
                    'course_id'         => $fields['courseid']['stringValue']    ?? $courseId,
                    'course_title'      => $fields['coursetitle']['stringValue'] ?? '',
                    'completed'         => $isCompleted,
                    'completed_lessons' => $completedLessons,
                    'completed_quizzes' => $completedQuizzes,
                    'updated_at'        => $fields['updatedAt']['timestampValue']
                                        ?? $fields['createdAt']['timestampValue']
                                        ?? '',
                ];

                $totalLessons += count($completedLessons);
                $totalQuizzes += count($completedQuizzes);
                if ($isCompleted) $completedCourses++;
            }

            $map[$userId] = [
                'total_completed_lessons' => $totalLessons,
                'total_completed_quizzes' => $totalQuizzes,
                'completed_courses'       => $completedCourses,
                'courses'                 => $courses,
            ];
        }

        Log::info('[fetchProgressMap] summary', [
            'users_with_progress' => count($map),
            'sample'              => array_slice(array_keys($map), 0, 3),
        ]);

        return $map;
    }

    private function fetchCosmicMap(string $projectId, string $token): array
    {
        $docs = $this->fetchAllDocuments($projectId, $token, 'cosmic_word_search_progress');
        $map  = [];

        foreach ($docs as $doc) {
            $fields = $doc['fields'] ?? [];
            $userId = basename($doc['name'] ?? '');
            if (empty($userId)) continue;

            $stars = $this->intFromFirestore($fields['totalStars'] ?? null);
            $completedLevels = 0;
            $totalLevels = 0;

            if (isset($fields['levels']['mapValue']['fields'])) {
                foreach ($fields['levels']['mapValue']['fields'] as $level) {
                    if (!isset($level['mapValue']['fields'])) continue;
                    $levelData = $level['mapValue']['fields'];
                    if (isset($levelData['levelId'])) {
                        $totalLevels++;
                        if (!empty($levelData['completed']['booleanValue'])) {
                            $completedLevels++;
                        }
                    }
                }
            }

            $map[$userId] = [
                'stars'            => $stars,
                'completed_levels' => $completedLevels,
                'total_levels'     => $totalLevels,
            ];
        }

        return $map;
    }

    private function fetchPlayersMap(string $projectId, string $token): array
    {
        $docs = $this->fetchAllDocuments($projectId, $token, 'players');
        $map  = [];

        foreach ($docs as $doc) {
            $fields = $doc['fields'] ?? [];
            $userId = basename($doc['name'] ?? '');
            if (empty($userId)) continue;

            $achievementsCount = 0;
            if (isset($fields['achievements']['arrayValue']['values'])) {
                $achievementsCount = count($fields['achievements']['arrayValue']['values']);
            }

            $map[$userId] = [
                'phase'        => $fields['phase']['stringValue'] ?? 'menu',
                'xp'           => $this->intFromFirestore($fields['xp'] ?? null),
                'achievements' => $achievementsCount,
            ];
        }

        return $map;
    }

    private function fetchEnrollmentCounts(string $projectId, string $token): array
    {
        $docs = $this->fetchAllDocuments($projectId, $token, 'courses');
        $counts = [];

        foreach ($docs as $doc) {
            $fields = $doc['fields'] ?? [];
            foreach ($fields['enrolledUsers']['arrayValue']['values'] ?? [] as $val) {
                $uid = $val['stringValue'] ?? null;
                if ($uid) {
                    $counts[$uid] = ($counts[$uid] ?? 0) + 1;
                }
            }
        }

        return $counts;
    }

    private function fetchVideoViews(string $projectId, string $token): array
    {
        $videos     = [];
        $totalViews = 0;

        $courses = $this->fetchAllDocuments($projectId, $token, 'courses');

        foreach ($courses as $courseDoc) {
            $courseId = basename($courseDoc['name'] ?? '');
            if (empty($courseId)) continue;

            $courseFields = $courseDoc['fields'] ?? [];
            $courseName   = $courseFields['coursename']['stringValue'] ?? '';

            $lessons = $this->fetchAllDocuments(
                $projectId,
                $token,
                "courses/{$courseId}/lessons"
            );

            foreach ($lessons as $lessonDoc) {
                $fields   = $lessonDoc['fields'] ?? [];
                $lessonId = basename($lessonDoc['name'] ?? '');
                if (empty($lessonId)) continue;

                $videoUrl = $fields['videourl']['stringValue'] ?? '';
                if ($videoUrl === '') continue;

                $views = $this->intFromFirestore($fields['viewCount'] ?? null);

                $videos[] = [
                    'video_id'  => $lessonId,
                    'lesson_id' => $fields['lessonid']['stringValue'] ?? $lessonId,
                    'course_id' => $fields['courseid']['stringValue'] ?? $courseId,
                    'course'    => $courseName,
                    'title'     => $fields['title']['stringValue'] ?? 'Untitled lesson',
                    'video_url' => $videoUrl,
                    'views'     => $views,
                ];

                $totalViews += $views;
            }
        }

        usort($videos, fn($a, $b) => $b['views'] <=> $a['views']);

        return [
            'videos'      => $videos,
            'total_views' => $totalViews,
            'top_video'   => $videos[0] ?? null,
        ];
    }

    private function fetchActivityLogStats(string $projectId, string $token): array
    {
        $lastActivity = [];
        $overTime = ['labels' => [], 'values' => []];

        try {
            $docs = $this->fetchAllDocuments($projectId, $token, 'activity_logs');

            $buckets = [];
            $startDate = now()->subDays(29)->startOfDay();
            for ($i = 0; $i < 30; $i++) {
                $day = $startDate->copy()->addDays($i)->format('Y-m-d');
                $buckets[$day] = 0;
                $overTime['labels'][] = $day;
            }

            foreach ($docs as $doc) {
                $fields = $doc['fields'] ?? [];
                $userId = $fields['userId']['stringValue']
                    ?? $fields['userid']['stringValue']
                    ?? null;
                $createdAt = $fields['createdAt']['timestampValue']
                    ?? $fields['createdAt']['stringValue']
                    ?? null;

                if ($userId && $createdAt) {
                    $existing = $lastActivity[$userId] ?? null;
                    if (!$existing || strtotime($createdAt) > strtotime($existing)) {
                        $lastActivity[$userId] = $createdAt;
                    }
                }

                if ($createdAt) {
                    $day = date('Y-m-d', strtotime($createdAt));
                    if (isset($buckets[$day])) {
                        $buckets[$day]++;
                    }
                }
            }

            foreach ($overTime['labels'] as $day) {
                $overTime['values'][] = $buckets[$day] ?? 0;
            }
        } catch (\Throwable $e) {
            Log::warning('Activity log fetch failed: ' . $e->getMessage());
        }

        return [$lastActivity, $overTime];
    }

    public function exportExcel(Request $request)
    {
        try {
            $report  = $this->fetchReport($request);
            $payload = $report->getData(true);

            if (!($payload['success'] ?? false)) {
                return response()->json($payload, 500);
            }

            $rows         = $payload['data']['rows']          ?? [];
            $totals       = $payload['data']['totals']        ?? [];
            $videoViews   = $payload['data']['video_views']   ?? [];
            $articleViews = $payload['data']['article_views'] ?? [];

            if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
                return $this->exportExcelNative($rows, $totals, $videoViews, $articleViews);
            }

            return $this->exportCsv($rows, $totals, $videoViews, $articleViews);
        } catch (\Throwable $e) {
            Log::error('Excel export failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function exportExcelNative(array $rows, array $totals, array $videoViews = [], array $articleViews = [])
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Summary');
        $sheet->setCellValue('A1', 'User Activity Report — Summary');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A3', 'Metric');
        $sheet->setCellValue('B3', 'Total');
        $sheet->getStyle('A3:B3')->getFont()->setBold(true);

        $summaryRows = [
            ['Articles Read',          $totals['articlesRead']      ?? 0],
            ['Total Article Views',    $articleViews['total']       ?? 0],
            ['Facts Read',             $totals['factsRead']         ?? 0],
            ['Videos Watched',         $totals['videosWatched']     ?? 0],
            ['Total Video Views',      $videoViews['total']         ?? 0],
            ['Games Completed',        $totals['gamesCompleted']    ?? 0],
            ['Personalities Viewed',   $totals['personalityview']   ?? 0],
            ['Quizzes Completed',      $totals['quizzesCompleted']  ?? 0],
            ['Cosmic Stars Earned',    $totals['cosmicStars']       ?? 0],
            ['Space Academy XP',       $totals['spaceXp']           ?? 0],
            ['Course Enrollments',     $totals['courseEnrollments'] ?? 0],
            ['Lessons Completed',      $totals['completedLessons']  ?? 0],
            ['Quizzes Completed (course)', $totals['completedQuizzes'] ?? 0],
            ['Courses Completed',      $totals['completedCourses']  ?? 0],
        ];
        $r = 4;
        foreach ($summaryRows as $row) {
            $sheet->setCellValue('A' . $r, $row[0]);
            $sheet->setCellValue('B' . $r, $row[1]);
            $r++;
        }
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(14);

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Per User');
        $headers = [
            'User ID', 'Username', 'Email', 'Role', 'Institution',
            'Articles Read', 'Facts Read', 'Videos Watched',
            'Games Completed', 'Personalities Viewed', 'Quizzes Completed',
            'Cosmic Stars', 'Cosmic Completed', 'Cosmic Total',
            'Space Phase', 'Space XP', 'Space Achievements',
            'Courses Enrolled', 'Lessons Completed', 'Courses Completed',
            'Last Activity', 'Engagement Score',
        ];
        $col = 1;
        foreach ($headers as $h) {
            $sheet2->setCellValueByColumnAndRow($col, 1, $h);
            $col++;
        }
        $sheet2->getStyle('A1:V1')->getFont()->setBold(true);
        $sheet2->freezePane('A2');

        $rowNum = 2;
        foreach ($rows as $r) {
            $prog = $r['progress'] ?? [];
            $sheet2->setCellValueByColumnAndRow(1,  $rowNum, $r['user_id']);
            $sheet2->setCellValueByColumnAndRow(2,  $rowNum, $r['username']);
            $sheet2->setCellValueByColumnAndRow(3,  $rowNum, $r['email']);
            $sheet2->setCellValueByColumnAndRow(4,  $rowNum, $r['role']);
            $sheet2->setCellValueByColumnAndRow(5,  $rowNum, $r['institution']);
            $sheet2->setCellValueByColumnAndRow(6,  $rowNum, $r['activities']['articlesRead']);
            $sheet2->setCellValueByColumnAndRow(7,  $rowNum, $r['activities']['factsRead']);
            $sheet2->setCellValueByColumnAndRow(8,  $rowNum, $r['activities']['videosWatched']);
            $sheet2->setCellValueByColumnAndRow(9,  $rowNum, $r['activities']['gamesCompleted']);
            $sheet2->setCellValueByColumnAndRow(10, $rowNum, $r['activities']['personalityview']);
            $sheet2->setCellValueByColumnAndRow(11, $rowNum, $r['activities']['quizzesCompleted']);
            $sheet2->setCellValueByColumnAndRow(12, $rowNum, $r['cosmic']['stars']);
            $sheet2->setCellValueByColumnAndRow(13, $rowNum, $r['cosmic']['completed_levels']);
            $sheet2->setCellValueByColumnAndRow(14, $rowNum, $r['cosmic']['total_levels']);
            $sheet2->setCellValueByColumnAndRow(15, $rowNum, $r['space']['phase']);
            $sheet2->setCellValueByColumnAndRow(16, $rowNum, $r['space']['xp']);
            $sheet2->setCellValueByColumnAndRow(17, $rowNum, $r['space']['achievements']);
            $sheet2->setCellValueByColumnAndRow(18, $rowNum, $r['courses_enrolled']);
            $sheet2->setCellValueByColumnAndRow(19, $rowNum, $prog['total_completed_lessons'] ?? 0);
            $sheet2->setCellValueByColumnAndRow(20, $rowNum, $prog['completed_courses'] ?? 0);
            $sheet2->setCellValueByColumnAndRow(21, $rowNum, $r['last_activity'] ?? '');
            $sheet2->setCellValueByColumnAndRow(22, $rowNum, $r['engagement_score']);
            $rowNum++;
        }
        for ($i = 1; $i <= 22; $i++) {
            $sheet2->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Video Views');
        $sheet3->setCellValue('A1', 'Video Views by Lesson');
        $sheet3->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet3->setCellValue('A3', 'Total Views');
        $sheet3->setCellValue('B3', $videoViews['total'] ?? 0);
        $sheet3->getStyle('A3:B3')->getFont()->setBold(true);

        $vh = ['#', 'Lesson Title', 'Course', 'Lesson ID', 'Views'];
        $col = 1;
        foreach ($vh as $h) {
            $sheet3->setCellValueByColumnAndRow($col, 5, $h);
            $col++;
        }
        $sheet3->getStyle('A5:E5')->getFont()->setBold(true);
        $sheet3->freezePane('A6');

        $rowNum = 6;
        foreach (($videoViews['videos'] ?? []) as $i => $v) {
            $sheet3->setCellValueByColumnAndRow(1, $rowNum, $i + 1);
            $sheet3->setCellValueByColumnAndRow(2, $rowNum, $v['title']);
            $sheet3->setCellValueByColumnAndRow(3, $rowNum, $v['course']);
            $sheet3->setCellValueByColumnAndRow(4, $rowNum, $v['lesson_id']);
            $sheet3->setCellValueByColumnAndRow(5, $rowNum, $v['views']);
            $rowNum++;
        }
        for ($i = 1; $i <= 5; $i++) {
            $sheet3->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Article Views');
        $sheet4->setCellValue('A1', 'Article Views');
        $sheet4->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet4->setCellValue('A3', 'Total Views');
        $sheet4->setCellValue('B3', $articleViews['total'] ?? 0);
        $sheet4->getStyle('A3:B3')->getFont()->setBold(true);

        $ah = ['#', 'Title', 'Category', 'Author', 'Views'];
        $col = 1;
        foreach ($ah as $h) {
            $sheet4->setCellValueByColumnAndRow($col, 5, $h);
            $col++;
        }
        $sheet4->getStyle('A5:E5')->getFont()->setBold(true);
        $sheet4->freezePane('A6');

        $rowNum = 6;
        foreach (($articleViews['articles'] ?? []) as $i => $a) {
            $sheet4->setCellValueByColumnAndRow(1, $rowNum, $i + 1);
            $sheet4->setCellValueByColumnAndRow(2, $rowNum, $a['title']);
            $sheet4->setCellValueByColumnAndRow(3, $rowNum, $a['category']);
            $sheet4->setCellValueByColumnAndRow(4, $rowNum, $a['author']);
            $sheet4->setCellValueByColumnAndRow(5, $rowNum, $a['views']);
            $rowNum++;
        }
        for ($i = 1; $i <= 5; $i++) {
            $sheet4->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $filename = 'user_activity_report_' . date('Y-m-d_His') . '.xlsx';
        $tmpPath  = storage_path('app/temp/' . $filename);
        if (!is_dir(dirname($tmpPath))) mkdir(dirname($tmpPath), 0755, true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function exportCsv(array $rows, array $totals, array $videoViews = [], array $articleViews = [])
    {
        $filename = 'user_activity_report_' . date('Y-m-d_His') . '.csv';
        $handle   = fopen('php://temp', 'r+');

        fputcsv($handle, ['User Activity Report — Summary']);
        fputcsv($handle, ['Metric', 'Total']);
        fputcsv($handle, ['Articles Read',        $totals['articlesRead']      ?? 0]);
        fputcsv($handle, ['Total Article Views',  $articleViews['total']       ?? 0]);
        fputcsv($handle, ['Facts Read',           $totals['factsRead']         ?? 0]);
        fputcsv($handle, ['Videos Watched',       $totals['videosWatched']     ?? 0]);
        fputcsv($handle, ['Total Video Views',    $videoViews['total']         ?? 0]);
        fputcsv($handle, ['Games Completed',      $totals['gamesCompleted']    ?? 0]);
        fputcsv($handle, ['Personalities Viewed', $totals['personalityview']   ?? 0]);
        fputcsv($handle, ['Quizzes Completed',    $totals['quizzesCompleted']  ?? 0]);
        fputcsv($handle, ['Cosmic Stars Earned',  $totals['cosmicStars']       ?? 0]);
        fputcsv($handle, ['Space Academy XP',     $totals['spaceXp']           ?? 0]);
        fputcsv($handle, ['Course Enrollments',   $totals['courseEnrollments'] ?? 0]);
        fputcsv($handle, ['Lessons Completed',    $totals['completedLessons']  ?? 0]);
        fputcsv($handle, ['Quizzes Completed (course)', $totals['completedQuizzes'] ?? 0]);
        fputcsv($handle, ['Courses Completed',    $totals['completedCourses']  ?? 0]);
        fputcsv($handle, []);
        fputcsv($handle, ['Per-User Breakdown']);
        fputcsv($handle, [
            'User ID', 'Username', 'Email', 'Role', 'Institution',
            'Articles Read', 'Facts Read', 'Videos Watched',
            'Games Completed', 'Personalities Viewed', 'Quizzes Completed',
            'Cosmic Stars', 'Cosmic Completed', 'Cosmic Total',
            'Space Phase', 'Space XP', 'Space Achievements',
            'Courses Enrolled', 'Lessons Completed', 'Courses Completed',
            'Last Activity', 'Engagement Score',
        ]);

        foreach ($rows as $r) {
            $prog = $r['progress'] ?? [];
            fputcsv($handle, [
                $r['user_id'], $r['username'], $r['email'], $r['role'], $r['institution'],
                $r['activities']['articlesRead'], $r['activities']['factsRead'],
                $r['activities']['videosWatched'], $r['activities']['gamesCompleted'],
                $r['activities']['personalityview'], $r['activities']['quizzesCompleted'],
                $r['cosmic']['stars'], $r['cosmic']['completed_levels'], $r['cosmic']['total_levels'],
                $r['space']['phase'], $r['space']['xp'], $r['space']['achievements'],
                $r['courses_enrolled'],
                $prog['total_completed_lessons'] ?? 0,
                $prog['completed_courses'] ?? 0,
                $r['last_activity'] ?? '', $r['engagement_score'],
            ]);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Video Views by Lesson']);
        fputcsv($handle, ['#', 'Lesson Title', 'Course', 'Lesson ID', 'Views']);
        foreach (($videoViews['videos'] ?? []) as $i => $v) {
            fputcsv($handle, [$i + 1, $v['title'], $v['course'], $v['lesson_id'], $v['views']]);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Article Views']);
        fputcsv($handle, ['#', 'Title', 'Category', 'Author', 'Views']);
        foreach (($articleViews['articles'] ?? []) as $i => $a) {
            fputcsv($handle, [$i + 1, $a['title'], $a['category'], $a['author'], $a['views']]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        try {
            $report  = $this->fetchReport($request);
            $payload = $report->getData(true);

            if (!($payload['success'] ?? false)) {
                return response()->json($payload, 500);
            }

            $data = $payload['data'];
            $html = view('admin.user-activities.pdf', ['data' => $data])->render();

            if (class_exists(\Dompdf\Dompdf::class)) {
                $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();

                $filename = 'user_activity_report_' . date('Y-m-d_His') . '.pdf';
                return response($dompdf->output(), 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ]);
            }

            return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        } catch (\Throwable $e) {
            Log::error('PDF export failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
