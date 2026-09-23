<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\WordPressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArticleController extends Controller
{
    public function index()
    {
        return view('admin.articles.index');
    }

    private function projectId(): string
    {
        return config('services.firebase.project_id');
    }

    private function token() { return session('firebase_token'); }

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
            'source'      => $fields['source']['stringValue']      ?? 'admin',
        ];
    }

    /**
     * Sanitize HTML while preserving formatting tags AND their attributes.
     * Keeps <strong>, <em>, <h1>-<h6>, <img src>, <a href>, <ul>/<ol>/<li>,
     * <blockquote>, <pre>, <code>, <table>, etc.
     *
     * Safety nets:
     *  - If DOMDocument fails to parse → return regex-cleaned input
     *  - If output is < 50% of input → return regex-cleaned input
     */
    private function sanitizeHtml(?string $html): string
    {
        if (!$html) return '';

        $inLen = strlen($html);

        Log::info('[sanitizeHtml] enter', [
            'in_len'    => $inLen,
            'has_img'   => (bool) preg_match('/<img\b/i', $html),
            'has_src'   => str_contains($html, 'src="'),
            'has_style' => str_contains($html, 'style="'),
        ]);

        // 1. Remove dangerous block elements with their contents
        $html = preg_replace(
            '#<(script|style|object|embed|form|input|button|textarea|select|noscript|template)\b[^>]*>.*?</\1>#is',
            '',
            $html
        );

        // 2. Remove dangling dangerous tags
        $html = preg_replace(
            '#<\s*/?\s*(script|object|embed|input|form|button|textarea|select|base|meta|link|noscript|template)\b[^>]*>?#i',
            '',
            $html
        );

        // 3. Strip inline event handlers
        $html = preg_replace('#\s+on\w+\s*=\s*"[^"]*"#i', '', $html);
        $html = preg_replace("#\s+on\w+\s*=\s*'[^']*'#i", '', $html);
        $html = preg_replace('#\s+on\w+\s*=\s*[^\s>]+#i', '', $html);

        // 4. Strip javascript:/vbscript: URLs
        $html = preg_replace(
            '#(href|src|data|action)\s*=\s*["\']?\s*(javascript|vbscript):[^"\'>\s]*#i',
            '',
            $html
        );

        // 5. Strip PHP/ASP/ERB tags
        $html = preg_replace('#<\?php.*?\?>#is', '', $html);
        $html = preg_replace('#<\?.*?\?>#is', '', $html);
        $html = preg_replace('#<%.*?%>#is', '', $html);

        // 6. Decode once for clean DOM parsing
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $regexCleaned = $html;

        $allowedTags = [
            'p','div','span','br','hr',
            'h1','h2','h3','h4','h5','h6',
            'ul','ol','li',
            'a','img',
            'strong','em','b','i','u','s','del','ins','mark','sub','sup','small',
            'blockquote','pre','code',
            'figure','figcaption',
            'table','thead','tbody','tfoot','tr','td','th','caption',
            'video','source','audio',
            'iframe',
        ];

        $globalAttrs = ['style','class','id','title','dir','lang'];

        $tagAttrs = [
            'a'      => ['href','target','rel','title','style','class'],
            'img'    => ['src','alt','width','height','style','class','loading'],
            'iframe' => ['src','width','height','frameborder','allow','allowfullscreen','title','style','class'],
            'video'  => ['src','poster','width','height','controls','style','class','autoplay','muted','loop'],
            'audio'  => ['src','controls','style','class','autoplay','muted','loop'],
            'source' => ['src','type'],
            'td'     => ['colspan','rowspan','style','class','align','valign'],
            'th'     => ['colspan','rowspan','style','class','align','valign','scope'],
            'li'     => ['data-list','data-list-value','style','class'],
        ];

        // 7. DOM-based sanitizer
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="__root__">'
            . $html .
            '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if (!$loaded) {
            Log::warning('[sanitizeHtml] DOMDocument parse failed — returning regex-cleaned input');
            return trim($regexCleaned);
        }

        $xpath = new \DOMXPath($dom);
        $nodes = [];
        foreach ($xpath->query('//*') as $n) $nodes[] = $n;

        foreach (array_reverse($nodes) as $node) {
            if (!($node instanceof \DOMElement)) continue;

            $tag = strtolower($node->nodeName);
            if ($tag === 'div' && $node->getAttribute('id') === '__root__') continue;

            if (!in_array($tag, $allowedTags, true)) {
                $hardRemove = in_array($tag, [
                    'script','style','object','embed','form','input','button',
                    'textarea','select','noscript','template','base','meta','link'
                ], true);

                if ($hardRemove) {
                    $node->parentNode->removeChild($node);
                } else {
                    while ($node->firstChild) {
                        $node->parentNode->insertBefore($node->firstChild, $node);
                    }
                    $node->parentNode->removeChild($node);
                }
                continue;
            }

            if ($node->hasAttributes()) {
                $remove = [];
                foreach ($node->attributes as $attr) {
                    $name  = $attr->name;
                    $lower = strtolower($name);
                    $val   = (string) $attr->value;

                    if (str_starts_with($lower, 'on')) { $remove[] = $name; continue; }

                    $ok = in_array($lower, $globalAttrs, true)
                       || str_starts_with($lower, 'data-')
                       || in_array($lower, $tagAttrs[$tag] ?? [], true);

                    if (!$ok) { $remove[] = $name; continue; }

                    if (in_array($lower, ['href','src','action'], true)) {
                        if (preg_match('#^\s*(javascript|vbscript):#i', $val)) {
                            $remove[] = $name; continue;
                        }
                        if (preg_match('#^\s*data:#i', $val)) {
                            if (!($tag === 'img' && preg_match('#^\s*data:image/#i', $val))) {
                                $remove[] = $name; continue;
                            }
                        }
                    }

                    if ($tag === 'iframe' && $lower === 'src') {
                        $allowedHosts = [
                            'youtube.com','www.youtube.com','youtube-nocookie.com',
                            'www.youtube-nocookie.com','player.vimeo.com','vimeo.com',
                        ];
                        $host = parse_url($val, PHP_URL_HOST) ?: '';
                        if (!in_array($host, $allowedHosts, true)) {
                            $remove[] = $name; continue;
                        }
                    }
                }
                foreach ($remove as $name) $node->removeAttribute($name);
            }
        }

        $root = $dom->getElementById('__root__');
        if (!$root) {
            Log::warning('[sanitizeHtml] root wrapper missing — returning regex-cleaned input');
            return trim($regexCleaned);
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        $out = trim($out);

        if ($inLen > 200 && strlen($out) < ($inLen * 0.5)) {
            Log::warning('[sanitizeHtml] output suspiciously short — returning regex-cleaned input', [
                'in_len'  => $inLen,
                'out_len' => strlen($out),
            ]);
            return trim($regexCleaned);
        }

        Log::info('[sanitizeHtml] exit', [
            'in_len'    => $inLen,
            'out_len'   => strlen($out),
            'has_img'   => (bool) preg_match('/<img\b/i', $out),
            'has_src'   => str_contains($out, 'src="'),
        ]);

        return $out;
    }

    /**
     * Strip HTML tags for plain-text excerpts (used only for WordPress import excerpt).
     * NOT used for content anymore.
     */
    private function htmlToPlainText(string $html): string
    {
        if ($html === '') return '';

        $html = preg_replace('#<(script|style|figure)[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<img[^>]*>#i', '', $html);
        $html = preg_replace('#</?(p|div|h[1-6]|li|tr|blockquote)[^>]*>#i', "\n\n", $html);
        $html = preg_replace('#<br\s*/?>#i', "\n", $html);

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("#\n{3,}#", "\n\n", $text);
        $text = preg_replace('#[ \t]+\n#', "\n", $text);
        $text = preg_replace("#\n[ \t]+#", "\n", $text);

        return trim($text);
    }

    // ---------- LIST (Firestore) ----------
    public function fetchArticles()
    {
        try {
            $projectId = $this->projectId();
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if (!$response->successful()) {
                Log::error('[fetchArticles] Firestore fetch failed', [
                    'status' => $response->status(),
                    'body'   => substr($response->body(), 0, 500),
                ]);
                return response()->json(['success' => false, 'message' => 'Failed to fetch articles'], 500);
            }

            $data = $response->json();
            $articles = [];
            foreach ($data['documents'] ?? [] as $doc) {
                $id = basename($doc['name']);
                $fields = $doc['fields'] ?? [];
                $article = $this->fieldsToArticle($id, $fields);
                if (empty($article['slug'])) continue;
                $articles[] = $article;
            }

            usort($articles, function ($a, $b) {
                return strcmp($b['publishedAt'] ?? '', $a['publishedAt'] ?? '');
            });

            return response()->json(['success' => true, 'data' => $articles]);

        } catch (\Exception $e) {
            Log::error('[fetchArticles] exception', ['msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- LIST (WordPress) ----------
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
                    // KEY CHANGE: sanitizeHtml preserves formatting, htmlToPlainText destroyed it
                    'content'     => $this->sanitizeHtml($post['content']['rendered'] ?? ''),
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
            Log::error('[fetchFromWordPress] exception', ['msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- SHOW ----------
    public function getArticle($id)
    {
        try {
            $projectId = $this->projectId();
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if (!$response->successful()) {
                return response()->json(['success' => false, 'message' => 'Article not found'], 404);
            }

            $fields = $response->json()['fields'] ?? [];
            $contentField = $fields['content']['stringValue'] ?? '';

            Log::info('[article.read] getArticle returned', [
                'id'                => $id,
                'content_field_len' => strlen($contentField),
                'has_img'           => (bool) preg_match('/<img\b/i', $contentField),
                'has_src'           => str_contains($contentField, 'src="'),
            ]);

            return response()->json(['success' => true, 'data' => $this->fieldsToArticle($id, $fields)]);

        } catch (\Exception $e) {
            Log::error('[getArticle] exception', ['id' => $id, 'msg' => $e->getMessage()]);
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
                'image'   => 'nullable|string|max:2048',
            ]);

            $rawContent = (string) $request->content;
            $rawLen = strlen($rawContent);

            Log::info('[article.save] RAW REQUEST (store)', [
                'content_len' => $rawLen,
                'has_img'     => (bool) preg_match('/<img\b/i', $rawContent),
                'has_src'     => str_contains($rawContent, 'src="'),
                'has_strong'  => (bool) preg_match('/<strong\b|<b\b/i', $rawContent),
            ]);

            $projectId = $this->projectId();
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            // KEY CHANGE: use sanitizeHtml (preserves formatting) not htmlToPlainText (strips everything)
            $sanitizedContent = $this->sanitizeHtml($rawContent);

            Log::info('[article.save] AFTER sanitizeHtml (store)', [
                'before_len' => $rawLen,
                'after_len'  => strlen($sanitizedContent),
                'has_img'    => (bool) preg_match('/<img\b/i', $sanitizedContent),
                'has_src'    => str_contains($sanitizedContent, 'src="'),
                'has_strong' => (bool) preg_match('/<strong\b|<b\b/i', $sanitizedContent),
            ]);

            if (trim(strip_tags($sanitizedContent)) === '' && !preg_match('/<img\b/i', $sanitizedContent)) {
                return response()->json(['success' => false, 'message' => 'Content cannot be empty'], 422);
            }

            $slug = $request->slug ?: \Str::slug($request->title);
            $docId = $slug;

            $checkUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$docId}";
            $existing = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get($checkUrl);
            if ($existing->successful()) {
                return response()->json(['success' => false, 'message' => 'An article with this slug already exists'], 409);
            }

            $now = now()->toISOString();

            // Auto-generate excerpt if empty
            $excerpt = $request->excerpt;
            if (empty($excerpt)) {
                $excerpt = mb_substr($this->htmlToPlainText($sanitizedContent), 0, 200);
            }

            $fields = [
                'title'       => ['stringValue' => $request->title],
                'slug'        => ['stringValue' => $slug],
                'author'      => ['stringValue' => $request->author ?? ''],
                'category'    => ['stringValue' => $request->category ?? ''],
                'categories'  => ['arrayValue' => ['values' => $request->category
                                    ? [['stringValue' => $request->category]] : []]],
                'excerpt'     => ['stringValue' => $excerpt],
                // KEY CHANGE: store sanitized HTML, not stripped plain text
                'content'     => ['stringValue' => $sanitizedContent],
                'image'       => ['stringValue' => $request->image ?? ''],
                'viewcount'   => ['integerValue' => 0],
                'publishedAt' => ['timestampValue' => $now],
                'createdAt'   => ['timestampValue' => $now],
                'updatedAt'   => ['timestampValue' => $now],
                'source'      => ['stringValue' => 'admin'],
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

            Log::error('[article.save] Firestore FAILED (store)', [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 800),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to create article'], 500);

        } catch (\Exception $e) {
            Log::error('[article.save] exception (store)', ['msg' => $e->getMessage()]);
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
                'image'   => 'nullable|string|max:2048',
            ]);

            $rawContent = (string) $request->content;
            $rawLen = strlen($rawContent);

            Log::info('[article.save] RAW REQUEST (update)', [
                'id'          => $id,
                'content_len' => $rawLen,
                'has_img'     => (bool) preg_match('/<img\b/i', $rawContent),
                'has_src'     => str_contains($rawContent, 'src="'),
            ]);

            $projectId = $this->projectId();
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            // KEY CHANGE: use sanitizeHtml
            $sanitizedContent = $this->sanitizeHtml($rawContent);

            Log::info('[article.save] AFTER sanitizeHtml (update)', [
                'id'         => $id,
                'before_len' => $rawLen,
                'after_len'  => strlen($sanitizedContent),
                'has_img'    => (bool) preg_match('/<img\b/i', $sanitizedContent),
                'has_src'    => str_contains($sanitizedContent, 'src="'),
            ]);

            if (trim(strip_tags($sanitizedContent)) === '' && !preg_match('/<img\b/i', $sanitizedContent)) {
                return response()->json(['success' => false, 'message' => 'Content cannot be empty'], 422);
            }

            $getUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$id}";
            $existing = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get($getUrl);
            $existingFields = $existing->successful() ? $existing->json()['fields'] ?? [] : [];

            $excerpt = $request->excerpt;
            if (empty($excerpt)) {
                $excerpt = mb_substr($this->htmlToPlainText($sanitizedContent), 0, 200);
            }

            $updateFields = [
                'title'      => ['stringValue' => $request->title],
                'author'     => ['stringValue' => $request->author ?? ''],
                'category'   => ['stringValue' => $request->category ?? ''],
                'categories' => ['arrayValue' => ['values' => $request->category
                                    ? [['stringValue' => $request->category]] : []]],
                'excerpt'    => ['stringValue' => $excerpt],
                // KEY CHANGE: store sanitized HTML
                'content'    => ['stringValue' => $sanitizedContent],
                'image'      => ['stringValue' => $request->image ?? ''],
                'updatedAt'  => ['timestampValue' => now()->toISOString()],
            ];

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

            Log::error('[article.save] Firestore FAILED (update)', [
                'id' => $id, 'status' => $response->status(),
                'body' => substr($response->body(), 0, 800),
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to update article'], 500);

        } catch (\Exception $e) {
            Log::error('[article.save] exception (update)', ['id' => $id, 'msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- DELETE ----------
    public function destroy($id)
    {
        try {
            $projectId = $this->projectId();
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
            Log::error('[article.delete] exception', ['id' => $id, 'msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- RENAME ----------
    public function rename(Request $request, $id)
    {
        try {
            $request->validate(['newSlug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/']);

            $projectId = $this->projectId();
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
                ['update' => [
                    'name'   => "projects/{$projectId}/databases/(default)/documents/articles/{$newSlug}",
                    'fields' => $fields,
                ]],
                ['delete' => "projects/{$projectId}/databases/(default)/documents/articles/{$id}"],
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
            Log::error('[article.rename] exception', ['id' => $id, 'msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
