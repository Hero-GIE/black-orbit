<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ArticleController extends Controller
{
    private function projectId(): string
    {
        return config('services.firebase.project_id');
    }

    private function token(): string
    {
        return app(\App\Services\FirebaseTokenService::class)->getToken();
    }

    private function fieldsToArticle($id, $fields)
    {
        $cats = [];
        foreach ($fields['categories']['arrayValue']['values'] ?? [] as $v) {
            $cats[] = $v['stringValue'] ?? '';
        }
        $cats = array_filter($cats);

        return [
            'id'          => $id,
            'title'       => $fields['title']['stringValue']       ?? '',
            'slug'        => $fields['slug']['stringValue']        ?? '',
            'author'      => $fields['author']['stringValue']      ?? '',
            'category'    => $fields['category']['stringValue']    ?? ($cats[0] ?? ''),
            'categories'  => $cats,
            'excerpt'     => $fields['excerpt']['stringValue']     ?? '',
            'content'     => $fields['content']['stringValue']     ?? '',
            'image'       => $fields['image']['stringValue']       ?? '',
            'link'        => $fields['link']['stringValue']        ?? '',
            'readingTime' => $fields['readingTime']['stringValue'] ?? '',
            'wordCount'   => (int)($fields['wordCount']['integerValue'] ?? 0),
            'viewcount'   => (int)($fields['viewcount']['integerValue'] ?? 0),
            'publishedAt' => $fields['publishedAt']['timestampValue'] ?? ($fields['createdAt']['timestampValue'] ?? ''),
            'updatedAt'   => $fields['updatedAt']['timestampValue'] ?? '',
            'source'      => $fields['source']['stringValue']      ?? 'wordpress',
        ];
    }

    // ---------- GET /api/articles ----------
    public function index()
    {
        try {
            $projectId = $this->projectId();
            $token = $this->token();

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles";
            $response = Http::withHeaders(['Authorization' => "Bearer {$token}"])
                ->timeout(30)
                ->get($url);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch articles',
                ], 500);
            }

            $articles = [];
            foreach ($response->json()['documents'] ?? [] as $doc) {
                $id = basename($doc['name']);
                $article = $this->fieldsToArticle($id, $doc['fields'] ?? []);

                if (empty($article['slug'])) continue;

                $articles[] = $article;
            }

            usort($articles, fn($a, $b) => strcmp($b['publishedAt'] ?? '', $a['publishedAt'] ?? ''));

            return response()->json([
                'success' => true,
                'message' => 'Articles fetched successfully',
                'count'   => count($articles),
                'data'    => $articles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ---------- GET /api/articles/{id} ----------
    public function show($id)
    {
        try {
            $projectId = $this->projectId();
            $token = $this->token();

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$id}";
            $response = Http::withHeaders(['Authorization' => "Bearer {$token}"])
                ->timeout(30)
                ->get($url);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Article fetched successfully',
                'data'    => $this->fieldsToArticle($id, $response->json()['fields'] ?? []),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ---------- POST /api/articles/{id}/view ----------
    public function recordView(Request $request, $id)
    {
        try {
            $projectId = $this->projectId();
            $token = $this->token();

            $uid = $request->input('user_id');

            if (!$uid) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user identity',
                ], 401);
            }

            $uid = preg_replace('#[/\\\\]#', '_', $uid);
            $auth = ['Authorization' => "Bearer {$token}"];
            $base = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$id}";

            // 1. Check if this user already viewed this article
            $viewDocUrl = "{$base}/views/{$uid}";
            $existing = Http::withHeaders($auth)->timeout(15)->get($viewDocUrl);

            if ($existing->successful()) {
                $article = Http::withHeaders($auth)->timeout(15)->get($base);
                $current = (int) ($article->json()['fields']['viewcount']['integerValue'] ?? 0);

                return response()->json([
                    'success'     => true,
                    'message'     => 'Article already viewed by this user',
                    'incremented' => false,
                    'viewcount'   => $current,
                ]);
            }

            // 2. Record the view on the article's subcollection
            Http::withHeaders($auth)->timeout(15)->patch($viewDocUrl, [
                'fields' => [
                    'viewedAt' => ['timestampValue' => now()->toISOString()],
                    'source'   => ['stringValue' => 'mobile'],
                ],
            ]);

            // 3. Also write to the user's history so we can list viewed articles per user
            $articleDoc = Http::withHeaders($auth)->timeout(15)->get($base);
            $articleFields = $articleDoc->json()['fields'] ?? [];
            $articleSlug = $articleFields['slug']['stringValue'] ?? $id;

            $historyUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}/history/{$articleSlug}";
            Http::withHeaders($auth)->timeout(15)->patch($historyUrl, [
                'fields' => [
                    'articleId'   => ['stringValue' => $id],
                    'slug'        => ['stringValue' => $articleSlug],
                    'title'       => ['stringValue' => $articleFields['title']['stringValue'] ?? ''],
                    'image'       => ['stringValue' => $articleFields['image']['stringValue'] ?? ''],
                    'category'    => ['stringValue' => $articleFields['category']['stringValue'] ?? ''],
                    'excerpt'     => ['stringValue' => $articleFields['excerpt']['stringValue'] ?? ''],
                    'readingTime' => ['stringValue' => $articleFields['readingTime']['stringValue'] ?? ''],
                    'viewedAt'    => ['timestampValue' => now()->toISOString()],
                ],
            ]);

            // 4. Increment the article's viewcount
            $current = (int) ($articleFields['viewcount']['integerValue'] ?? 0);
            $newCount = $current + 1;

            Http::withHeaders($auth)
                ->timeout(15)
                ->patch($base . '?updateMask.fieldPaths=viewcount', [
                    'fields' => ['viewcount' => ['integerValue' => $newCount]],
                ]);

            return response()->json([
                'success'     => true,
                'message'     => 'View recorded successfully',
                'incremented' => true,
                'viewcount'   => $newCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ---------- GET /api/articles/viewed ----------
    // Returns the list of articles the current user has viewed.
    public function viewed(Request $request)
    {
        try {
            $projectId = $this->projectId();
            $token = $this->token();

            $uid = $request->input('user_id');

            if (!$uid) {
                return response()->json([
                    'success' => false,
                    'message' => 'No user identity',
                ], 401);
            }

            $uid = preg_replace('#[/\\\\]#', '_', $uid);
            $auth = ['Authorization' => "Bearer {$token}"];

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}/history";
            $response = Http::withHeaders($auth)->timeout(30)->get($url);

            if (!$response->successful()) {
                // No history yet — return empty, not an error
                return response()->json([
                    'success' => true,
                    'message' => 'No viewed articles yet',
                    'count'   => 0,
                    'data'    => [],
                ]);
            }

            $articles = [];
            foreach ($response->json()['documents'] ?? [] as $doc) {
                $fields = $doc['fields'] ?? [];

                $articles[] = [
                    'id'          => $fields['articleId']['stringValue']  ?? basename($doc['name']),
                    'slug'        => $fields['slug']['stringValue']       ?? '',
                    'title'       => $fields['title']['stringValue']      ?? '',
                    'image'       => $fields['image']['stringValue']      ?? '',
                    'category'    => $fields['category']['stringValue']   ?? '',
                    'excerpt'     => $fields['excerpt']['stringValue']    ?? '',
                    'readingTime' => $fields['readingTime']['stringValue'] ?? '',
                    'viewedAt'    => $fields['viewedAt']['timestampValue'] ?? '',
                ];
            }

            // Most recently viewed first
            usort($articles, fn($a, $b) => strcmp($b['viewedAt'] ?? '', $a['viewedAt'] ?? ''));

            return response()->json([
                'success' => true,
                'message' => 'Viewed articles fetched successfully',
                'count'   => count($articles),
                'data'    => $articles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
