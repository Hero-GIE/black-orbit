<?php

namespace App\Console\Commands;

use App\Services\WordPressService;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncWordPressArticles extends Command
{
    protected $signature = 'articles:sync-wordpress';
    protected $description = 'Pull posts from WordPress and cache them into Firestore';

    public function handle(WordPressService $wp): int
    {
        $projectId = config('services.firebase.project_id');
        $token     = $this->getServiceToken();

        if (!$token) {
            $this->error('Failed to obtain Firebase access token.');
            return self::FAILURE;
        }

        $page  = 1;
        $total = 0;

        do {
            try {
                $posts = $wp->fetchPosts($page, 20);
            } catch (\Exception $e) {
                $this->error("WordPress fetch failed on page {$page}: " . $e->getMessage());
                return self::FAILURE;
            }

            if (empty($posts)) break;

            foreach ($posts as $post) {
                $slug = $post['slug'] ?? null;
                if (!$slug) continue;

                $fields = $wp->mapToFirestoreFields($post);

                $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$slug}";
                $existing = Http::withHeaders(['Authorization' => "Bearer {$token}"])->get($url);

                if ($existing->successful()) {
                    $existingUpdated = $existing->json()['fields']['updatedAt']['timestampValue'] ?? '';
                    if ($existingUpdated >= ($fields['updatedAt']['timestampValue'] ?? '')) {
                        continue;
                    }
                }

                $res = Http::withHeaders(['Authorization' => "Bearer {$token}"])
                    ->timeout(30)
                    ->patch($url, ['fields' => $fields]);

                if ($res->successful()) {
                    $total++;
                } else {
                    $this->warn("Failed to save {$slug}: HTTP {$res->status()}");
                }
            }

            $page++;
        } while (count($posts) === 20);

        $this->info("Synced {$total} articles from WordPress.");
        return self::SUCCESS;
    }

    private function getServiceToken(): string
   {
    return app(\App\Services\FirebaseTokenService::class)->getToken();
   }
}
