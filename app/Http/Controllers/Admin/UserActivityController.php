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

        $snapshot = $this->getCachedSnapshot($projectId, $token);

        $activities       = $snapshot['activities'];
        $usersMap         = $snapshot['users'];
        $cosmicMap        = $snapshot['cosmic'];
        $playersMap       = $snapshot['players'];
        $enrollmentCounts = $snapshot['enrollments'];
        $lastActivityMap  = $snapshot['lastActivity'];
        $overTime         = $snapshot['overTime'];

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
        ];
        $byRole = [];
        $activeUserCount = 0;

        foreach ($activities as $doc) {
            $fields = $doc['fields'] ?? [];
            $userId = $fields['userid']['stringValue'] ?? basename($doc['name'] ?? '');
            if (empty($userId)) continue;

            $user   = $usersMap[$userId] ?? null;
            $cosmic = $cosmicMap[$userId] ?? ['stars' => 0, 'completed_levels' => 0, 'total_levels' => 0];
            $player = $playersMap[$userId] ?? ['phase' => '—', 'xp' => 0, 'achievements' => 0];
            $enrolled = $enrollmentCounts[$userId] ?? 0;
            $lastActivity = $lastActivityMap[$userId] ?? null;

            $articlesRead     = (int) ($fields['articlesRead']['integerValue']      ?? 0);
            $factsRead        = (int) ($fields['factsRead']['integerValue']         ?? 0);
            $videosWatched    = (int) ($fields['videosWatched']['integerValue']     ?? 0);
            $gamesCompleted   = (int) ($fields['gamesCompleted']['integerValue']    ?? 0);
            $personalityView  = (int) ($fields['personalityview']['integerValue']   ?? 0);
            $quizzesCompleted = (int) ($fields['quizzesCompleted']['integerValue']  ?? 0);

            $engagementScore = $articlesRead + $factsRead + $videosWatched
                + $gamesCompleted + $personalityView + $quizzesCompleted
                + $cosmic['stars'] + (int) floor($player['xp'] / 100);

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
                'courses_enrolled' => $enrolled,
                'last_activity'    => $lastActivity,
                'engagement_score' => $engagementScore,
            ];

            if ($engagementScore === 0 && $enrolled === 0) continue;
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

        usort($rows, function ($a, $b) use ($sortBy, $sortDir) {
            $av = $this->sortValue($a, $sortBy);
            $bv = $this->sortValue($b, $sortBy);
            return $sortDir === 'asc' ? $av <=> $bv : $bv <=> $av;
        });

        $topUsers = array_slice(array_map(fn($r) => [
            'username'         => $r['username'],
            'role'             => $r['role'],
            'engagement_score' => $r['engagement_score'],
        ], $rows), 0, 10);

        $engagementByType = [
            ['label' => 'Articles Read',        'value' => $totals['articlesRead'],     'color' => '#8ab4ff'],
            ['label' => 'Facts Read',           'value' => $totals['factsRead'],        'color' => '#75c594'],
            ['label' => 'Videos Watched',       'value' => $totals['videosWatched'],    'color' => '#ffcc66'],
            ['label' => 'Games Completed',      'value' => $totals['gamesCompleted'],   'color' => '#f69595'],
            ['label' => 'Personalities Viewed', 'value' => $totals['personalityview'],  'color' => '#b0b7bd'],
            ['label' => 'Quizzes Completed',    'value' => $totals['quizzesCompleted'], 'color' => '#96e6c9'],
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
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('UserActivity report failed: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error building report: ' . $e->getMessage(),
        ], 500);
    }
}

private function getCachedSnapshot(string $projectId, string $token): array
{
    $cacheKey = 'user_activity_snapshot:' . sha1($projectId);

    return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($projectId, $token) {
        $activities = $this->fetchAllDocuments($projectId, $token, 'user_activities');
        $users       = $this->fetchUsersMap($projectId, $token);
        $cosmic      = $this->fetchCosmicMap($projectId, $token);
        $players     = $this->fetchPlayersMap($projectId, $token);
        $enrollments = $this->fetchEnrollmentCounts($projectId, $token);

        [$lastActivity, $overTime] = $this->fetchActivityLogStats($projectId, $token);

        return [
            'activities'    => $activities,
            'users'         => $users,
            'cosmic'        => $cosmic,
            'players'       => $players,
            'enrollments'   => $enrollments,
            'lastActivity'  => $lastActivity,
            'overTime'      => $overTime,
        ];
    });
}

    private function sortValue(array $row, string $sortBy): int
    {
        return match ($sortBy) {
            'articles'    => $row['activities']['articlesRead'],
            'facts'       => $row['activities']['factsRead'],
            'videos'      => $row['activities']['videosWatched'],
            'games'       => $row['activities']['gamesCompleted'],
            'personalities' => $row['activities']['personalityview'],
            'quizzes'     => $row['activities']['quizzesCompleted'],
            'stars'       => $row['cosmic']['stars'],
            'xp'          => $row['space']['xp'],
            'courses'     => $row['courses_enrolled'],
            'username'    => (int) ord($row['username'][0] ?? 'A'),
            default       => $row['engagement_score'],
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
            if (empty($uid)) {
                continue;
            }
            $map[$uid] = [
                'username'    => $fields['username']['stringValue']    ?? 'Unknown',
                'email'       => $fields['email']['stringValue']       ?? '',
                'role'        => $fields['role']['stringValue']        ?? 'student',
                'institution' => $fields['institution']['stringValue'] ?? '',
            ];
        }

        return $map;
    }

    private function fetchCosmicMap(string $projectId, string $token): array
    {
        $docs = $this->fetchAllDocuments($projectId, $token, 'cosmic_word_search_progress');
        $map  = [];

        foreach ($docs as $doc) {
            $fields = $doc['fields'] ?? [];
            $userId = basename($doc['name'] ?? '');
            if (empty($userId)) {
                continue;
            }

            $stars = (int) ($fields['totalStars']['integerValue'] ?? 0);
            $completedLevels = 0;
            $totalLevels = 0;

            if (isset($fields['levels']['mapValue']['fields'])) {
                foreach ($fields['levels']['mapValue']['fields'] as $level) {
                    if (!isset($level['mapValue']['fields'])) {
                        continue;
                    }
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
            // Players doc name is usually the user uid
            $userId = basename($doc['name'] ?? '');
            if (empty($userId)) {
                continue;
            }

            $achievementsCount = 0;
            if (isset($fields['achievements']['arrayValue']['values'])) {
                $achievementsCount = count($fields['achievements']['arrayValue']['values']);
            }

            $map[$userId] = [
                'phase'        => $fields['phase']['stringValue'] ?? 'menu',
                'xp'           => (int) ($fields['xp']['integerValue'] ?? 0),
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
            $enrolled = $fields['enrolledUsers']['arrayValue']['values'] ?? [];
            foreach ($enrolled as $val) {
                $uid = $val['stringValue']
                    ?? $val['mapValue']['fields']['uid']['stringValue']
                    ?? null;
                if ($uid) {
                    $counts[$uid] = ($counts[$uid] ?? 0) + 1;
                }
            }
        }

        return $counts;
    }

    private function fetchActivityLogStats(string $projectId, string $token): array
    {
        $lastActivity = [];
        $overTime = ['labels' => [], 'values' => []];

        try {
            $docs = $this->fetchAllDocuments($projectId, $token, 'activity_logs');

            // Bucket by day for last 30 days
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
        } catch (\Exception $e) {
            Log::warning('Activity log fetch failed: ' . $e->getMessage());
        }

        return [$lastActivity, $overTime];
    }


    public function exportExcel(Request $request)
    {
        try {
            $report = $this->fetchReport($request);
            $payload = $report->getData(true);

            if (!($payload['success'] ?? false)) {
                return response()->json($payload, 500);
            }

            $rows   = $payload['data']['rows'] ?? [];
            $totals = $payload['data']['totals'] ?? [];

            if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
                return $this->exportExcelNative($rows, $totals);
            }

            // Fallback: CSV (Excel opens it natively)
            return $this->exportCsv($rows, $totals);

        } catch (\Exception $e) {
            Log::error('Excel export failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function exportExcelNative(array $rows, array $totals)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // Sheet 1: Summary
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Summary');
        $sheet->setCellValue('A1', 'User Activity Report — Summary');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A3', 'Metric');
        $sheet->setCellValue('B3', 'Total');
        $sheet->getStyle('A3:B3')->getFont()->setBold(true);

        $summaryRows = [
            ['Articles Read',        $totals['articlesRead']      ?? 0],
            ['Facts Read',           $totals['factsRead']         ?? 0],
            ['Videos Watched',       $totals['videosWatched']     ?? 0],
            ['Games Completed',      $totals['gamesCompleted']    ?? 0],
            ['Personalities Viewed', $totals['personalityview']   ?? 0],
            ['Quizzes Completed',    $totals['quizzesCompleted']  ?? 0],
            ['Cosmic Stars Earned',  $totals['cosmicStars']       ?? 0],
            ['Space Academy XP',     $totals['spaceXp']           ?? 0],
            ['Course Enrollments',   $totals['courseEnrollments'] ?? 0],
        ];
        $r = 4;
        foreach ($summaryRows as $row) {
            $sheet->setCellValue('A' . $r, $row[0]);
            $sheet->setCellValue('B' . $r, $row[1]);
            $r++;
        }
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(14);

        // Sheet 2: Per-user breakdown
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Per User');
        $headers = [
            'User ID', 'Username', 'Email', 'Role', 'Institution',
            'Articles Read', 'Facts Read', 'Videos Watched',
            'Games Completed', 'Personalities Viewed', 'Quizzes Completed',
            'Cosmic Stars', 'Cosmic Completed', 'Cosmic Total',
            'Space Phase', 'Space XP', 'Space Achievements',
            'Courses Enrolled', 'Last Activity', 'Engagement Score',
        ];
        $col = 1;
        foreach ($headers as $h) {
            $sheet2->setCellValueByColumnAndRow($col, 1, $h);
            $col++;
        }
        $sheet2->getStyle('A1:T1')->getFont()->setBold(true);
        $sheet2->freezePane('A2');

        $rowNum = 2;
        foreach ($rows as $r) {
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
            $sheet2->setCellValueByColumnAndRow(19, $rowNum, $r['last_activity'] ?? '');
            $sheet2->setCellValueByColumnAndRow(20, $rowNum, $r['engagement_score']);
            $rowNum++;
        }

        // Auto-size columns
        for ($i = 1; $i <= 20; $i++) {
            $sheet2->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $filename = 'user_activity_report_' . date('Y-m-d_His') . '.xlsx';
        $tmpPath = storage_path('app/temp/' . $filename);
        if (!is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0755, true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function exportCsv(array $rows, array $totals)
    {
        $filename = 'user_activity_report_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['User Activity Report — Summary']);
        fputcsv($handle, ['Metric', 'Total']);
        fputcsv($handle, ['Articles Read',        $totals['articlesRead']      ?? 0]);
        fputcsv($handle, ['Facts Read',           $totals['factsRead']         ?? 0]);
        fputcsv($handle, ['Videos Watched',       $totals['videosWatched']     ?? 0]);
        fputcsv($handle, ['Games Completed',      $totals['gamesCompleted']    ?? 0]);
        fputcsv($handle, ['Personalities Viewed', $totals['personalityview']   ?? 0]);
        fputcsv($handle, ['Quizzes Completed',    $totals['quizzesCompleted']  ?? 0]);
        fputcsv($handle, ['Cosmic Stars Earned',  $totals['cosmicStars']       ?? 0]);
        fputcsv($handle, ['Space Academy XP',     $totals['spaceXp']           ?? 0]);
        fputcsv($handle, ['Course Enrollments',   $totals['courseEnrollments'] ?? 0]);
        fputcsv($handle, []);
        fputcsv($handle, ['Per-User Breakdown']);
        fputcsv($handle, [
            'User ID', 'Username', 'Email', 'Role', 'Institution',
            'Articles Read', 'Facts Read', 'Videos Watched',
            'Games Completed', 'Personalities Viewed', 'Quizzes Completed',
            'Cosmic Stars', 'Cosmic Completed', 'Cosmic Total',
            'Space Phase', 'Space XP', 'Space Achievements',
            'Courses Enrolled', 'Last Activity', 'Engagement Score',
        ]);

        foreach ($rows as $r) {
            fputcsv($handle, [
                $r['user_id'], $r['username'], $r['email'], $r['role'], $r['institution'],
                $r['activities']['articlesRead'], $r['activities']['factsRead'],
                $r['activities']['videosWatched'], $r['activities']['gamesCompleted'],
                $r['activities']['personalityview'], $r['activities']['quizzesCompleted'],
                $r['cosmic']['stars'], $r['cosmic']['completed_levels'], $r['cosmic']['total_levels'],
                $r['space']['phase'], $r['space']['xp'], $r['space']['achievements'],
                $r['courses_enrolled'], $r['last_activity'] ?? '', $r['engagement_score'],
            ]);
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
            $report = $this->fetchReport($request);
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

        } catch (\Exception $e) {
            Log::error('PDF export failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
