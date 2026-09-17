<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\WordPressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ArticleController extends Controller
{
    public function index()
    {
        return view('admin.articles.index');
    }

    private function token() { return session('firebase_token'); }

    private function fieldsToArticle($id, $fields)
    {
        // Category array
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
            'source'      => $fields['source']['stringValue']      ?? 'admin', // ← NEW
        ];
    }

    // ---------- LIST (Firestore) ----------
    public function fetchArticles()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if (!$response->successful()) {
                return response()->json(['success' => false, 'message' => 'Failed to fetch articles'], 500);
            }

            $data = $response->json();
            $articles = [];
            foreach ($data['documents'] ?? [] as $doc) {
                $id = basename($doc['name']);
                $fields = $doc['fields'] ?? [];
                $article = $this->fieldsToArticle($id, $fields);

                if (empty($article['slug'])) continue;

                unset($article['content']);
                $articles[] = $article;
            }

            usort($articles, function ($a, $b) {
                return strcmp($b['publishedAt'] ?? '', $a['publishedAt'] ?? '');
            });

            return response()->json(['success' => true, 'data' => $articles]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- LIST (WordPress, published only) ---------- ← NEW
    public function fetchFromWordPress(WordPressService $wp)
    {
        try {
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $page    = (int) request('page', 1);
            $perPage = (int) request('per_page', 20);

            $posts = $wp->fetchPosts($page, $perPage);

            $articles = array_map(function ($post) use ($wp) {
                $fields = $wp->mapToFirestoreFields($post);

                $plainExcerpt = trim(strip_tags($post['excerpt']['rendered'] ?? ''));
                if ($plainExcerpt === '') {
                    $plainExcerpt = mb_substr(trim(strip_tags($post['content']['rendered'] ?? '')), 0, 160) . '…';
                }

                return [
                    'id'          => $post['slug'],
                    'wpId'        => $post['id'],
                    'title'       => html_entity_decode($post['title']['rendered'] ?? ''),
                    'slug'        => $post['slug'],
                    'author'      => $fields['author']['stringValue'] ?? '',
                    'category'    => $fields['category']['stringValue'] ?? '',
                    'categories'  => array_map(fn($v) => $v['stringValue'], $fields['categories']['arrayValue']['values'] ?? []),
                    'excerpt'     => $plainExcerpt,
                    'content'     => $post['content']['rendered'] ?? '',
                    'image'       => $fields['image']['stringValue'] ?? '',
                    'link'        => $post['link'] ?? '',
                    'readingTime' => $fields['readingTime']['stringValue'] ?? '',
                    'wordCount'   => $fields['wordCount']['integerValue'] ?? 0,
                    'viewcount'   => 0,
                    'publishedAt' => $fields['publishedAt']['timestampValue'] ?? '',
                    'updatedAt'   => $fields['updatedAt']['timestampValue'] ?? '',
                    'source'      => 'wordpress',
                    'status'      => 'publish',
                ];
            }, $posts);

            return response()->json(['success' => true, 'data' => $articles]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- SHOW ----------
    public function getArticle($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if (!$response->successful()) {
                return response()->json(['success' => false, 'message' => 'Article not found'], 404);
            }

            $fields = $response->json()['fields'] ?? [];
            return response()->json(['success' => true, 'data' => $this->fieldsToArticle($id, $fields)]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- CREATE ----------
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title'   => 'required|string|max:255',
                'slug'    => 'nullable|string|max:255',
                'author'  => 'nullable|string|max:255',
                'category'=> 'nullable|string|max:255',
                'excerpt' => 'nullable|string',
                'content' => 'required|string',
                'image'   => 'nullable|url',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $slug = $request->slug ?: \Str::slug($request->title);
            $docId = $slug;

            $checkUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$docId}";
            $existing = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get($checkUrl);
            if ($existing->successful()) {
                return response()->json(['success' => false, 'message' => 'An article with this slug already exists'], 409);
            }

            $now = now()->toISOString();
            $fields = [
                'title'       => ['stringValue' => $request->title],
                'slug'        => ['stringValue' => $slug],
                'author'      => ['stringValue' => $request->author ?? ''],
                'category'    => ['stringValue' => $request->category ?? ''],
                'categories'  => ['arrayValue' => ['values' => $request->category
                                    ? [['stringValue' => $request->category]] : []]],
                'excerpt'     => ['stringValue' => $request->excerpt ?? ''],
                'content'     => ['stringValue' => $request->content],
                'image'       => ['stringValue' => $request->image ?? ''],
                'viewcount'   => ['integerValue' => 0],
                'publishedAt' => ['timestampValue' => $now],
                'createdAt'   => ['timestampValue' => $now],
                'updatedAt'   => ['timestampValue' => $now],
                'source'      => ['stringValue' => 'admin'], // ← NEW
            ];

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$docId}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->timeout(30)
                ->patch($url, ['fields' => $fields]);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Article created: ' . $request->title,
                    'New article added by ' . session('firebase_username', 'Admin'),
                    ['articleId' => $docId]
                );
                return response()->json(['success' => true, 'message' => 'Article created successfully', 'id' => $docId]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to create article'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- UPDATE ----------
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'title'   => 'required|string|max:255',
                'author'  => 'nullable|string|max:255',
                'category'=> 'nullable|string|max:255',
                'excerpt' => 'nullable|string',
                'content' => 'required|string',
                'image'   => 'nullable|url',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $getUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$id}";
            $existing = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get($getUrl);
            $existingFields = $existing->successful() ? $existing->json()['fields'] ?? [] : [];

            $updateFields = [
                'title'      => ['stringValue' => $request->title],
                'author'     => ['stringValue' => $request->author ?? ''],
                'category'   => ['stringValue' => $request->category ?? ''],
                'categories' => ['arrayValue' => ['values' => $request->category
                                    ? [['stringValue' => $request->category]] : []]],
                'excerpt'    => ['stringValue' => $request->excerpt ?? ''],
                'content'    => ['stringValue' => $request->content],
                'image'      => ['stringValue' => $request->image ?? ''],
                'updatedAt'  => ['timestampValue' => now()->toISOString()],
            ];

            // Preserve immutable fields (including source)
            foreach (['createdAt', 'publishedAt', 'viewcount', 'slug', 'source'] as $preserve) {
                if (isset($existingFields[$preserve])) {
                    $updateFields[$preserve] = $existingFields[$preserve];
                }
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->timeout(30)
                ->patch($url, ['fields' => $updateFields]);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Article updated: ' . $request->title,
                    'Modified by ' . session('firebase_username', 'Admin'),
                    ['articleId' => $id]
                );
                return response()->json(['success' => true, 'message' => 'Article updated successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to update article'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- DELETE ----------
    public function destroy($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->delete($url);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Article deleted',
                    'Removed by ' . session('firebase_username', 'Admin'),
                    ['articleId' => $id]
                );
                return response()->json(['success' => true, 'message' => 'Article deleted successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to delete article'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- RENAME (slug change) ----------
    public function rename(Request $request, $id)
    {
        try {
            $request->validate(['newSlug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/']);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $newSlug = $request->newSlug;
            if ($newSlug === $id) {
                return response()->json(['success' => true, 'message' => 'Slug unchanged']);
            }

            $getUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$id}";
            $getRes = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get($getUrl);
            if (!$getRes->successful()) {
                return response()->json(['success' => false, 'message' => 'Article not found'], 404);
            }

            $fields = $getRes->json()['fields'] ?? [];
            $fields['slug'] = ['stringValue' => $newSlug];
            $fields['updatedAt'] = ['timestampValue' => now()->toISOString()];

            $writes = [
                [
                    'update' => [
                        'name'   => "projects/{$projectId}/databases/(default)/documents/articles/{$newSlug}",
                        'fields' => $fields,
                    ],
                ],
                [
                    'delete' => "projects/{$projectId}/databases/(default)/documents/articles/{$id}",
                ],
            ];

            $batchUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents:batchWrite";
            $batchRes = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->timeout(30)
                ->post($batchUrl, ['writes' => $writes]);

            if ($batchRes->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Article renamed',
                    "Slug changed from {$id} to {$newSlug}",
                    ['oldId' => $id, 'newId' => $newSlug]
                );
                return response()->json(['success' => true, 'message' => 'Slug updated', 'newId' => $newSlug]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to rename article'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
