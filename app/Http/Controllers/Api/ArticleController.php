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
     * Sanitize HTML content — keeps formatting tags AND their attributes
     * (src, href, style, class, alt, width, height, etc.) so that
     * TinyMCE formatting and inserted images survive the round-trip.
     */
    private function sanitizeHtml(?string $html): string
    {
        if (!$html) return '';

        $inLen = strlen($html);

        Log::info('[sanitizeHtml] ▶ enter', [
            'in_len'    => $inLen,
            'has_img'   => (bool) preg_match('/<img\b/i', $html),
            'has_src'   => (bool) str_contains($html, 'src="'),
            'has_style' => (bool) str_contains($html, 'style="'),
            'head'      => substr($html, 0, 500),
        ]);

        // Decode entities once so sanitization is consistent
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // --- Remove dangerous block elements entirely (with their contents) ---
        $html = preg_replace(
            '#<(script|style|iframe|object|embed|form|input|button|textarea|select|noscript|template)\b[^>]*>.*?</\1>#is',
            '',
            $html
        );

        // --- Remove leftover self-closing / unclosed dangerous tags ---
        $html = preg_replace(
            '#<\s*/?\s*(script|iframe|object|embed|input|form|button|textarea|select|base|meta|link|noscript|template)\b[^>]*>?#i',
            '',
            $html
        );

        // --- Strip inline event handlers (onclick, onerror, onload, etc.) ---
        $html = preg_replace('#\s+on\w+\s*=\s*"[^"]*"#i', '', $html);
        $html = preg_replace("#\s+on\w+\s*=\s*'[^']*'#i", '', $html);
        $html = preg_replace('#\s+on\w+\s*=\s*[^\s>]+#i', '', $html);

        // --- Strip javascript: / vbscript: URLs ---
        $html = preg_replace(
            '#(href|src|data|action)\s*=\s*(["\']?)\s*(javascript|vbscript):[^"\'>\s]*\2#i',
            '',
            $html
        );

        // --- Strip PHP / ASP / ERB tags ---
        $html = preg_replace('#<\?php.*?\?>#is', '', $html);
        $html = preg_replace('#<\?.*?\?>#is', '', $html);
        $html = preg_replace('#<%.*?%>#is', '', $html);

        Log::info('[sanitizeHtml] after regex passes', [
            'len'       => strlen($html),
            'has_img'   => (bool) preg_match('/<img\b/i', $html),
            'has_src'   => (bool) str_contains($html, 'src="'),
            'has_style' => (bool) str_contains($html, 'style="'),
        ]);

        // --- Prefer HTMLPurifier if available ---
        if (class_exists(\Mews\Purifier\Facades\Purifier::class)) {
            try {
                Log::info('[sanitizeHtml] using Purifier');
                $cleaned = \Mews\Purifier\Facades\Purifier::clean($html, [
                    'HTML.Allowed' =>
                        'div[style|class],p[style|class],span[style|class],br,hr,'
                      . 'h1[style|class],h2[style|class],h3[style|class],h4[style|class],h5[style|class],h6[style|class],'
                      . 'b,strong,i,em,u,s,del,ins,mark,sub,sup,small,'
                      . 'a[href|target|rel|title|style|class],'
                      . 'img[src|alt|width|height|style|class|loading],'
                      . 'ul[style|class],ol[style|class],li[style|class],'
                      . 'blockquote[style|class],pre[style|class],code,'
                      . 'table[style|class],thead,tbody,tfoot,tr,td[style|class],th[style|class],caption,'
                      . 'figure[style|class],figcaption[style|class],'
                      . 'video[src|poster|width|height|controls],source[src|type]',
                    'CSS.AllowedProperties' =>
                        'font,font-size,font-weight,font-style,font-family,color,background-color,'
                      . 'text-align,text-decoration,margin,padding,width,height,'
                      . 'border,border-radius,display,float,clear',
                    'AutoFormat.AutoParagraph' => false,
                    'AutoFormat.RemoveEmpty'   => false,
                    'Attr.AllowedFrameTargets' => '_blank,_self,_parent,_top',
                    'URI.AllowedSchemes'       => [
                        'http' => true, 'https' => true, 'mailto' => true, 'data' => true,
                    ],
                ]);

                if (is_string($cleaned) && trim($cleaned) !== '') {
                    Log::info('[sanitizeHtml] ◀ exit (Purifier)', [
                        'out_len' => strlen($cleaned),
                        'has_img' => (bool) preg_match('/<img\b/i', $cleaned),
                        'has_src' => (bool) str_contains($cleaned, 'src="'),
                        'has_style' => (bool) str_contains($cleaned, 'style="'),
                        'head'    => substr($cleaned, 0, 500),
                    ]);
                    return trim($cleaned);
                }
            } catch (\Throwable $e) {
                Log::warning('Purifier failed, falling back to DOM sanitizer: ' . $e->getMessage());
            }
        } else {
            Log::info('[sanitizeHtml] Purifier NOT available — using manualSanitize');
        }

        // --- Fallback: DOM-based sanitizer that KEEPS attributes ---
        $out = $this->manualSanitize($html);

        Log::info('[sanitizeHtml] ◀ exit (manual)', [
            'out_len'   => strlen($out),
            'has_img'   => (bool) preg_match('/<img\b/i', $out),
            'has_src'   => (bool) str_contains($out, 'src="'),
            'has_style' => (bool) str_contains($out, 'style="'),
            'head'      => substr($out, 0, 500),
        ]);

        return $out;
    }

    /**
     * DOM-based sanitizer. Preserves attributes on allowed tags.
     */
    private function manualSanitize(string $html): string
    {
        $inLen = strlen($html);

        Log::debug('[manualSanitize] ▶ enter', [
            'in_len'    => $inLen,
            'has_img'   => (bool) preg_match('/<img\b/i', $html),
            'has_src'   => (bool) str_contains($html, 'src="'),
            'has_style' => (bool) str_contains($html, 'style="'),
        ]);

        if (trim($html) === '') {
            Log::debug('[manualSanitize] empty input — exit');
            return '';
        }

        $allowedTags = [
            'p','div','span','br','hr',
            'h1','h2','h3','h4','h5','h6',
            'ul','ol','li',
            'a','img',
            'strong','em','b','i','u','s','del','ins','mark','sub','sup','small',
            'blockquote','pre','code',
            'figure','figcaption',
            'table','thead','tbody','tfoot','tr','td','th','caption','colgroup','col',
            'video','source','audio',
        ];

        $allowedAttrs = [
            '*'      => ['style','class','id','title','dir','lang'],
            'a'      => ['href','target','rel','title','style','class'],
            'img'    => ['src','alt','width','height','style','class','loading','srcset','sizes'],
            'video'  => ['src','poster','width','height','controls','style','class','autoplay','muted','loop','playsinline'],
            'audio'  => ['src','controls','style','class','autoplay','muted','loop'],
            'source' => ['src','type','srcset','media'],
            'td'     => ['colspan','rowspan','style','class','align','valign'],
            'th'     => ['colspan','rowspan','style','class','align','valign','scope'],
            'col'    => ['span','style','class','width'],
            'colgroup' => ['span','style','class','width'],
        ];

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);

        $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="__sanitize_root__">'
            . $html .
            '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $allNodes = $xpath->query('//*');
        $nodes = [];
        foreach ($allNodes as $n) $nodes[] = $n;

        foreach (array_reverse($nodes) as $node) {
            if (!($node instanceof \DOMElement)) continue;

            $tag = strtolower($node->nodeName);

            if ($tag === 'div' && $node->getAttribute('id') === '__sanitize_root__') {
                continue;
            }

            if (!in_array($tag, $allowedTags, true)) {
                $removeSubtree = in_array($tag, [
                    'script','style','iframe','object','embed','form','input','button',
                    'textarea','select','noscript','template','base','meta','link'
                ], true);

                Log::debug('[manualSanitize] 🗑 dropped tag', [
                    'tag'             => $tag,
                    'removed_subtree' => $removeSubtree,
                ]);

                if ($removeSubtree) {
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
                $attrNames = [];
                foreach ($node->attributes as $attr) $attrNames[] = $attr->name;

                foreach ($attrNames as $name) {
                    $lower = strtolower($name);

                    if (str_starts_with($lower, 'on')) {
                        Log::debug('[manualSanitize] 🗑 dropped event handler', [
                            'tag' => $tag, 'attr' => $lower,
                        ]);
                        $node->removeAttribute($name);
                        continue;
                    }

                    if (!$this->isAllowedAttribute($tag, $lower, $allowedAttrs)) {
                        $origVal = $node->getAttribute($name);
                        Log::debug('[manualSanitize] 🗑 dropped attribute', [
                            'tag'  => $tag,
                            'attr' => $lower,
                            'val'  => substr((string) $origVal, 0, 120),
                        ]);
                        $node->removeAttribute($name);
                        continue;
                    }

                    if (in_array($lower, ['href','src','poster','action','data'], true)) {
                        $val = trim((string)$node->getAttribute($name));
                        if (preg_match('#^\s*(javascript|vbscript):#i', $val)) {
                            Log::debug('[manualSanitize] 🗑 dropped unsafe URL', [
                                'tag' => $tag, 'attr' => $lower, 'val' => substr($val, 0, 120),
                            ]);
                            $node->removeAttribute($name);
                            continue;
                        }
                        if (preg_match('#^\s*data:#i', $val)) {
                            if (!($tag === 'img' && preg_match('#^\s*data:image/#i', $val))) {
                                Log::debug('[manualSanitize] 🗑 dropped non-image data URI', [
                                    'tag' => $tag, 'attr' => $lower, 'val' => substr($val, 0, 120),
                                ]);
                                $node->removeAttribute($name);
                                continue;
                            }
                        }
                    }
                }
            }
        }

        $root = $dom->getElementById('__sanitize_root__');
        if (!$root) {
            Log::warning('[manualSanitize] ⚠️ root wrapper not found — falling back to strip_tags');
            return trim(strip_tags($html));
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        $out = trim($out);

        Log::debug('[manualSanitize] ◀ exit', [
            'in_len'              => $inLen,
            'out_len'             => strlen($out),
            'delta'               => $inLen - strlen($out),
            'img_before'          => preg_match_all('/<img\b/i', $html),
            'img_after'           => preg_match_all('/<img\b/i', $out),
            'img_with_src_before' => preg_match_all('/<img[^>]+src="/i', $html),
            'img_with_src_after'  => preg_match_all('/<img[^>]+src="/i', $out),
            'strong_before'       => preg_match_all('/<strong\b/i', $html),
            'strong_after'        => preg_match_all('/<strong\b/i', $out),
            'style_before'        => substr_count($html, 'style="'),
            'style_after'         => substr_count($out, 'style="'),
            'out_head'            => substr($out, 0, 500),
        ]);

        return $out;
    }

    private function isAllowedAttribute(string $tag, string $attr, array $allowedAttrs): bool
    {
        if (in_array($attr, $allowedAttrs['*'] ?? [], true)) {
            return true;
        }
        if (str_starts_with($attr, 'data-')) {
            return true;
        }
        if (in_array($attr, $allowedAttrs[$tag] ?? [], true)) {
            return true;
        }
        return false;
    }

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

    // ---------- LIST ----------
    public function fetchArticles()
    {
        try {
            $projectId = $this->projectId();
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if (!$response->successful()) {
                Log::error('[fetchArticles] ❌ Firestore fetch failed', [
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

            Log::info('[fetchArticles] 📥 returned', [
                'count'             => count($articles),
                'with_img'          => count(array_filter($articles, fn($a) => preg_match('/<img\b/i', $a['content'] ?? ''))),
                'with_src'          => count(array_filter($articles, fn($a) => str_contains($a['content'] ?? '', 'src="'))),
                'with_style'        => count(array_filter($articles, fn($a) => str_contains($a['content'] ?? '', 'style="'))),
            ]);

            return response()->json(['success' => true, 'data' => $articles]);

        } catch (\Exception $e) {
            Log::error('[fetchArticles] exception', ['msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- LIST (WordPress, published only) ----------
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
                Log::warning('[getArticle] not found', ['id' => $id, 'status' => $response->status()]);
                return response()->json(['success' => false, 'message' => 'Article not found'], 404);
            }

            $fields = $response->json()['fields'] ?? [];
            $contentField = $fields['content']['stringValue'] ?? '';

            Log::info('[article.read] 📥 getArticle returned', [
                'id'                => $id,
                'content_field_len' => strlen($contentField),
                'has_img'           => (bool) preg_match('/<img\b/i', $contentField),
                'has_src'           => str_contains($contentField, 'src="'),
                'has_style'         => str_contains($contentField, 'style="'),
                'head'              => substr($contentField, 0, 500),
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

            Log::info('[article.save] 📥 RAW REQUEST (store)', [
                'title'        => $request->title,
                'slug'         => $request->slug,
                'content_len'  => strlen((string) $request->content),
                'has_img'      => (bool) preg_match('/<img\b/i', (string) $request->content),
                'has_src'      => str_contains((string) $request->content, 'src="'),
                'has_strong'   => (bool) preg_match('/<strong\b|<b\b/i', (string) $request->content),
                'has_style'    => str_contains((string) $request->content, 'style="'),
                'content_head' => substr((string) $request->content, 0, 800),
            ]);

            $projectId = $this->projectId();
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $rawLen = strlen((string) $request->content);
            $sanitizedContent = $this->sanitizeHtml($request->content);

            Log::info('[article.save] 🧼 AFTER sanitizeHtml (store)', [
                'before_len'  => $rawLen,
                'after_len'   => strlen($sanitizedContent),
                'delta'       => $rawLen - strlen($sanitizedContent),
                'has_img'     => (bool) preg_match('/<img\b/i', $sanitizedContent),
                'has_src'     => str_contains($sanitizedContent, 'src="'),
                'has_strong'  => (bool) preg_match('/<strong\b|<b\b/i', $sanitizedContent),
                'has_style'   => str_contains($sanitizedContent, 'style="'),
                'content_head'=> substr($sanitizedContent, 0, 800),
            ]);

            if (trim(strip_tags($sanitizedContent)) === '') {
                Log::warning('[article.save] ⚠️ content empty after sanitize (store)');
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
            $fields = [
                'title'       => ['stringValue' => $request->title],
                'slug'        => ['stringValue' => $slug],
                'author'      => ['stringValue' => $request->author ?? ''],
                'category'    => ['stringValue' => $request->category ?? ''],
                'categories'  => ['arrayValue' => ['values' => $request->category
                                    ? [['stringValue' => $request->category]] : []]],
                'excerpt'     => ['stringValue' => $request->excerpt ?? ''],
                'content'     => ['stringValue' => $sanitizedContent],
                'image'       => ['stringValue' => $request->image ?? ''],
                'viewcount'   => ['integerValue' => 0],
                'publishedAt' => ['timestampValue' => $now],
                'createdAt'   => ['timestampValue' => $now],
                'updatedAt'   => ['timestampValue' => $now],
                'source'      => ['stringValue' => 'admin'],
            ];

            $contentField = $fields['content']['stringValue'] ?? '';

            Log::info('[article.save] 🚀 SENDING to Firestore (store)', [
                'docId'             => $docId,
                'content_field_len' => strlen($contentField),
                'has_img'           => (bool) preg_match('/<img\b/i', $contentField),
                'has_src'           => str_contains($contentField, 'src="'),
                'has_style'         => str_contains($contentField, 'style="'),
                'content_head'      => substr($contentField, 0, 800),
            ]);

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$docId}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->timeout(30)
                ->patch($url, ['fields' => $fields]);

            if ($response->successful()) {
                Log::info('[article.save] ✅ Firestore OK (store)', [
                    'docId'  => $docId,
                    'status' => $response->status(),
                ]);

                ActivityLogger::log(
                    'admin_action',
                    'Article created: ' . $request->title,
                    'New article added by ' . session('firebase_username', 'Admin'),
                    ['articleId' => $docId]
                );
                return response()->json(['success' => true, 'message' => 'Article created successfully', 'id' => $docId]);
            }

            Log::error('[article.save] ❌ Firestore FAILED (store)', [
                'docId'  => $docId,
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

            Log::info('[article.save] 📥 RAW REQUEST (update)', [
                'id'           => $id,
                'title'        => $request->title,
                'content_len'  => strlen((string) $request->content),
                'has_img'      => (bool) preg_match('/<img\b/i', (string) $request->content),
                'has_src'      => str_contains((string) $request->content, 'src="'),
                'has_strong'   => (bool) preg_match('/<strong\b|<b\b/i', (string) $request->content),
                'has_style'    => str_contains((string) $request->content, 'style="'),
                'content_head' => substr((string) $request->content, 0, 800),
            ]);

            $projectId = $this->projectId();
            $token = $this->token();
            if (!$token) return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);

            $rawLen = strlen((string) $request->content);
            $sanitizedContent = $this->sanitizeHtml($request->content);

            Log::info('[article.save] 🧼 AFTER sanitizeHtml (update)', [
                'id'          => $id,
                'before_len'  => $rawLen,
                'after_len'   => strlen($sanitizedContent),
                'delta'       => $rawLen - strlen($sanitizedContent),
                'has_img'     => (bool) preg_match('/<img\b/i', $sanitizedContent),
                'has_src'     => str_contains($sanitizedContent, 'src="'),
                'has_strong'  => (bool) preg_match('/<strong\b|<b\b/i', $sanitizedContent),
                'has_style'   => str_contains($sanitizedContent, 'style="'),
                'content_head'=> substr($sanitizedContent, 0, 800),
            ]);

            if (trim(strip_tags($sanitizedContent)) === '') {
                Log::warning('[article.save] ⚠️ content empty after sanitize (update)', ['id' => $id]);
                return response()->json(['success' => false, 'message' => 'Content cannot be empty'], 422);
            }

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
                'content'    => ['stringValue' => $sanitizedContent],
                'image'      => ['stringValue' => $request->image ?? ''],
                'updatedAt'  => ['timestampValue' => now()->toISOString()],
            ];

            foreach (['createdAt', 'publishedAt', 'viewcount', 'slug', 'source'] as $preserve) {
                if (isset($existingFields[$preserve])) {
                    $updateFields[$preserve] = $existingFields[$preserve];
                }
            }

            if ($request->filled('slug') && $request->slug !== $id) {
                $newSlug = preg_replace('/[^a-z0-9-]/', '', strtolower($request->slug));
                if ($newSlug && $newSlug !== $id) {
                    $updateFields['slug'] = ['stringValue' => $newSlug];
                }
            }

            $contentField = $updateFields['content']['stringValue'] ?? '';

            Log::info('[article.save] 🚀 SENDING to Firestore (update)', [
                'id'                => $id,
                'content_field_len' => strlen($contentField),
                'has_img'           => (bool) preg_match('/<img\b/i', $contentField),
                'has_src'           => str_contains($contentField, 'src="'),
                'has_style'         => str_contains($contentField, 'style="'),
                'content_head'      => substr($contentField, 0, 800),
            ]);

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/articles/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->timeout(30)
                ->patch($url, ['fields' => $updateFields]);

            if ($response->successful()) {
                Log::info('[article.save] ✅ Firestore OK (update)', [
                    'id'     => $id,
                    'status' => $response->status(),
                ]);

                ActivityLogger::log(
                    'admin_action',
                    'Article updated: ' . $request->title,
                    'Modified by ' . session('firebase_username', 'Admin'),
                    ['articleId' => $id]
                );
                return response()->json(['success' => true, 'message' => 'Article updated successfully']);
            }

            Log::error('[article.save] ❌ Firestore FAILED (update)', [
                'id'     => $id,
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 800),
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
                Log::info('[article.delete] ✅ deleted', ['id' => $id]);
                ActivityLogger::log(
                    'admin_action',
                    'Article deleted',
                    'Removed by ' . session('firebase_username', 'Admin'),
                    ['articleId' => $id]
                );
                return response()->json(['success' => true, 'message' => 'Article deleted successfully']);
            }

            Log::error('[article.delete] ❌ failed', [
                'id' => $id, 'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to delete article'], 500);

        } catch (\Exception $e) {
            Log::error('[article.delete] exception', ['id' => $id, 'msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------- RENAME (slug change) ----------
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
                Log::info('[article.rename] ✅', ['old' => $id, 'new' => $newSlug]);
                ActivityLogger::log(
                    'admin_action',
                    'Article renamed',
                    "Slug changed from {$id} to {$newSlug}",
                    ['oldId' => $id, 'newId' => $newSlug]
                );
                return response()->json(['success' => true, 'message' => 'Slug updated', 'newId' => $newSlug]);
            }

            Log::error('[article.rename] ❌', [
                'old' => $id, 'new' => $newSlug,
                'status' => $batchRes->status(),
                'body' => substr($batchRes->body(), 0, 500),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to rename article'], 500);

        } catch (\Exception $e) {
            Log::error('[article.rename] exception', ['id' => $id, 'msg' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
