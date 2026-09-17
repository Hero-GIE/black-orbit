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

    // GET /api/articles
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
                return response()->json(['success' => false, 'message' => 'Failed to fetch articles'], 500);
            }

            $articles = [];
            foreach ($response->json()['documents'] ?? [] as $doc) {
                $id = basename($doc['name']);
                $article = $this->fieldsToArticle($id, $doc['fields'] ?? []);

                if (empty($article['slug'])) continue;

                // unset($article['content']);
                $articles[] = $article;
            }

            usort($articles, fn($a, $b) => strcmp($b['publishedAt'] ?? '', $a['publishedAt'] ?? ''));

            return response()->json(['success' => true, 'data' => $articles]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // GET /api/articles/{id}
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
                return response()->json(['success' => false, 'message' => 'Article not found'], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $this->fieldsToArticle($id, $response->json()['fields'] ?? []),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

public function recordView(Request $request, $id)
{
    try {
        $projectId = $this->projectId();
        $token = $this->token();

        $uid = $request->input('user_id');

        if (!$uid) {
            return response()->json(['success' => false, 'message' => 'No user identity'], 401);
        }

        $uid = preg_replace('#[/\\\\]#', '_', $uid);

        $auth = ['Authorization' => "Bearer {$token}"];
        $base = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$id}";

        $viewDocUrl = "{$base}/views/{$uid}";
        $existing = Http::withHeaders($auth)->timeout(15)->get($viewDocUrl);

        if ($existing->successful()) {
            $article = Http::withHeaders($auth)->timeout(15)->get($base);
            $current = (int) ($article->json()['fields']['viewcount']['integerValue'] ?? 0);

            return response()->json([
                'success'     => true,
                'incremented' => false,
                'viewcount'   => $current,
            ]);
        }

        Http::withHeaders($auth)->timeout(15)->patch($viewDocUrl, [
            'fields' => [
                'viewedAt' => ['timestampValue' => now()->toISOString()],
                'source'   => ['stringValue' => 'mobile'],
            ],
        ]);

        $article = Http::withHeaders($auth)->timeout(15)->get($base);
        $current = (int) ($article->json()['fields']['viewcount']['integerValue'] ?? 0);
        $newCount = $current + 1;

        Http::withHeaders($auth)
            ->timeout(15)
            ->patch($base . '?updateMask.fieldPaths=viewcount', [
                'fields' => ['viewcount' => ['integerValue' => $newCount]],
            ]);

        return response()->json([
            'success'     => true,
            'incremented' => true,
            'viewcount'   => $newCount,
        ]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
}
}
