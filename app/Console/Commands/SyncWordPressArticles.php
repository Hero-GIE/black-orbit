<?php

namespace App\Console\Commands;

use App\Services\FirebaseTokenService;
use App\Services\WordPressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncWordPressArticles extends Command
{
    protected $signature = 'articles:sync-wordpress';
    protected $description = 'Pull published posts from WordPress and cache them into Firestore';

    public function handle(WordPressService $wp, FirebaseTokenService $tokenService): int
    {
        $projectId = config('services.firebase.project_id');

        if (!$projectId) {
            $this->error('FIREBASE_PROJECT_ID is not set.');
            return self::FAILURE;
        }

        try {
            $token = $tokenService->getToken();
        } catch (\Exception $e) {
            $this->error('Failed to obtain Firebase token: ' . $e->getMessage());
            return self::FAILURE;
        }

        $authHeader = ['Authorization' => "Bearer {$token}"];
        $page  = 1;
        $total = 0;
        $skipped = 0;
        $failed  = 0;

        do {
            try {
                $posts = $wp->fetchPosts($page, 20);
            } catch (\Exception $e) {
                $this->error("WordPress fetch failed on page {$page}: " . $e->getMessage());
                return self::FAILURE;
            }

            if (empty($posts)) {
                break;
            }

            foreach ($posts as $post) {
                $slug = $post['slug'] ?? null;
                if (!$slug) {
                    continue;
                }

                $fields = $wp->mapToFirestoreFields($post);

                // Look up any existing doc with this slug (regardless of doc ID)
                $existingDocId = null;
                $existingFields = [];

                $queryRes = Http::withHeaders($authHeader)->timeout(30)->post(
                    "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents:runQuery",
                    [
                        'structuredQuery' => [
                            'from'  => [['collectionId' => 'articles']],
                            'where' => [
                                'fieldFilter' => [
                                    'field' => ['fieldPath' => 'slug'],
                                    'op'    => 'EQUAL',
                                    'value' => ['stringValue' => $slug],
                                ],
                            ],
                            'limit' => 1,
                        ],
                    ]
                );

                if ($queryRes->successful()) {
                    foreach ($queryRes->json() as $row) {
                        if (!empty($row['document'])) {
                            $existingDocId  = basename($row['document']['name']);
                            $existingFields = $row['document']['fields'] ?? [];
                            break;
                        }
                    }
                }

                $docId = $existingDocId ?: $slug;

                if ($existingDocId) {
                    $existingSource = $existingFields['source']['stringValue'] ?? 'wordpress';

                    if ($existingSource === 'admin') {
                        $skipped++;
                        continue;
                    }

                    // Preserve viewcount across re-syncs
                    if (isset($existingFields['viewcount'])) {
                        $fields['viewcount'] = $existingFields['viewcount'];
                    }
                }

                $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$docId}";
                $res = Http::withHeaders($authHeader)->timeout(30)->patch($url, ['fields' => $fields]);

                if ($res->successful()) {
                    $total++;
                } else {
                    $this->warn("Failed to save {$docId}: HTTP {$res->status()}");
                    $failed++;
                }
            }

            $page++;
        } while (count($posts) === 20);

        $this->info("Synced {$total} article(s). Skipped {$skipped}. Failed {$failed}.");
        return self::SUCCESS;
    }
}
