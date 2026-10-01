<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirestoreServiceForVideos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AppInstallController extends Controller
{
    /**
     * Called by mobile apps on FIRST launch.
     * POST /api/apps/install
     */
    public function track(Request $request)
    {
        $data = $request->validate([
            'deviceId'    => 'required|string|max:128',
            'platform'    => 'required|in:ios,android',
            'version'     => 'nullable|string|max:32',
            'deviceModel' => 'nullable|string|max:64',
        ]);

        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $bearer    = $this->getBearer();
            if (!$bearer) {
                return response()->json(['success' => false, 'message' => 'Server not configured'], 500);
            }

            $deviceId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['deviceId']);
            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/app_installs/{$deviceId}";

            $fields = [
                'platform'    => ['stringValue' => $data['platform']],
                'installedAt' => ['timestampValue' => now()->toISOString()],
            ];
            if (!empty($data['version']))     $fields['version']     = ['stringValue' => $data['version']];
            if (!empty($data['deviceModel'])) $fields['deviceModel'] = ['stringValue' => $data['deviceModel']];

            $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                ->timeout(30)
                ->patch($url, ['fields' => $fields]);

            if (!$resp->successful()) {
                Log::error('[app:install] write failed', ['body' => $resp->body()]);
                return response()->json(['success' => false, 'message' => 'Write failed'], 500);
            }

            Cache::forget('app_stats');

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('[app:install] FAILED', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Called by mobile apps on EVERY launch.
     * POST /api/apps/open
     */
    public function trackOpen(Request $request)
    {
        $data = $request->validate([
            'deviceId' => 'required|string|max:128',
            'platform' => 'required|in:ios,android',
            'version'  => 'nullable|string|max:32',
        ]);

        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $bearer    = $this->getBearer();
            if (!$bearer) {
                return response()->json(['success' => false, 'message' => 'Server not configured'], 500);
            }

            $deviceId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['deviceId']);
            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/app_opens";

            $fields = [
                'deviceId' => ['stringValue' => $deviceId],
                'platform' => ['stringValue' => $data['platform']],
                'openedAt' => ['timestampValue' => now()->toISOString()],
            ];
            if (!empty($data['version'])) {
                $fields['version'] = ['stringValue' => $data['version']];
            }

            $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                ->timeout(30)
                ->post($url, ['fields' => $fields]);

            if (!$resp->successful()) {
                Log::error('[app:open] write failed', ['body' => $resp->body()]);
                return response()->json(['success' => false, 'message' => 'Write failed'], 500);
            }

            Cache::forget('app_stats');

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('[app:open] FAILED', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Aggregated stats for the admin dashboard.
     * GET /api/apps/stats  OR  GET /admin/api/apps/stats
     *
     * Returns: { installs: {ios, android, total}, opens: {today, dauToday, total} }
     */
    public function stats(Request $request)
    {
        try {
            $data = Cache::remember('app_stats', 300, function () {
                $projectId = env('FIREBASE_PROJECT_ID');
                $bearer    = $this->getBearer();
                if (!$bearer) return null;

                // ── Installs ──
                $installs = ['ios' => 0, 'android' => 0, 'total' => 0, 'other' => 0];
                $pageToken = null;
                do {
                    $q = ['pageSize' => 300];
                    if ($pageToken) $q['pageToken'] = $pageToken;
                    $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                        ->timeout(60)
                        ->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/app_installs?" . http_build_query($q));
                    if (!$resp->successful()) break;

                    $json = $resp->json();
                    foreach ($json['documents'] ?? [] as $doc) {
                        $p = $doc['fields']['platform']['stringValue'] ?? 'other';
                        if (isset($installs[$p])) $installs[$p]++;
                        else $installs['other']++;
                        $installs['total']++;
                    }
                    $pageToken = $json['nextPageToken'] ?? null;
                } while ($pageToken);

                // ── Opens (today + DAU today + total) ──
                $today       = now()->toDateString();         // YYYY-MM-DD
                $opensToday  = 0;
                $totalOpens  = 0;
                $dauToday    = [];                            // set of deviceIds
                $pageToken   = null;

                do {
                    $q = ['pageSize' => 300];
                    if ($pageToken) $q['pageToken'] = $pageToken;
                    $resp = Http::withHeaders(['Authorization' => 'Bearer ' . $bearer])
                        ->timeout(60)
                        ->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/app_opens?" . http_build_query($q));
                    if (!$resp->successful()) break;

                    $json = $resp->json();
                    foreach ($json['documents'] ?? [] as $doc) {
                        $f = $doc['fields'] ?? [];
                        $openedAt = $f['openedAt']['timestampValue'] ?? null;
                        $deviceId = $f['deviceId']['stringValue'] ?? '';
                        if (!$openedAt) continue;

                        $totalOpens++;
                        if (substr($openedAt, 0, 10) === $today) {
                            $opensToday++;
                            $dauToday[$deviceId] = true;
                        }
                    }
                    $pageToken = $json['nextPageToken'] ?? null;
                } while ($pageToken);

                return [
                    'installs' => [
                        'ios'     => $installs['ios'],
                        'android' => $installs['android'],
                        'total'   => $installs['total'],
                    ],
                    'opens' => [
                        'today'    => $opensToday,
                        'dauToday' => count($dauToday),
                        'total'    => $totalOpens,
                    ],
                ];
            });

            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            Log::error('[app:stats] FAILED', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function getBearer(): ?string
    {
        $svc = app(FirestoreServiceForVideos::class);
        $ref = new \ReflectionClass($svc);
        $m   = $ref->getMethod('token');
        $m->setAccessible(true);
        return $m->invoke($svc);
    }
}
