<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WordPressService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.wordpress.base_url'), '/');
    }

    public function fetchPosts(int $page = 1, int $perPage = 20): array
    {
        $response = Http::timeout(30)->get("{$this->baseUrl}/wp-json/wp/v2/posts", [
            'page'     => $page,
            'per_page' => $perPage,
            '_embed'   => true,
        ]);

        if (!$response->successful()) {
            throw new \Exception("WordPress fetch failed: " . $response->status());
        }

        return $response->json();
    }

    public function mapToFirestoreFields(array $post): array
    {
        $featuredImage = $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? '';
        $author        = $post['_embedded']['author'][0]['name'] ?? '';
        $categories    = [];

        foreach ($post['_embedded']['wp:term'][0] ?? [] as $term) {
            $categories[] = $term['name'];
        }

        $contentHtml = $post['content']['rendered'] ?? '';
        $contentText = $this->htmlToPlainText($contentHtml);
        $wordCount   = str_word_count($contentText);
        $readingTime = max(1, (int) ceil($wordCount / 200)) . ' min read';

        $excerpt = trim(strip_tags($post['excerpt']['rendered'] ?? ''));
        if ($excerpt === '') {
            $excerpt = mb_substr($contentText, 0, 160) . '...';
        }

        return [
            'title'       => ['stringValue' => html_entity_decode($post['title']['rendered'] ?? '')],
            'slug'        => ['stringValue' => $post['slug'] ?? ''],
            'author'      => ['stringValue' => $author],
            'category'    => ['stringValue' => $categories[0] ?? ''],
            'categories'  => ['arrayValue' => ['values' => array_map(
                fn($c) => ['stringValue' => $c], $categories
            )]],
            'excerpt'     => ['stringValue' => $excerpt],
            'content'     => ['stringValue' => $contentText],
            'image'       => ['stringValue' => $featuredImage],
            'link'        => ['stringValue' => $post['link'] ?? ''],
            'readingTime' => ['stringValue' => $readingTime],
            'wordCount'   => ['integerValue' => $wordCount],
            'viewcount'   => ['integerValue' => 0],
            'publishedAt' => ['timestampValue' => $this->toIso($post['date_gmt'] ?? null)],
            'updatedAt'   => ['timestampValue' => $this->toIso($post['modified_gmt'] ?? null)],
            'createdAt'   => ['timestampValue' => $this->toIso($post['date_gmt'] ?? null)],
            'source'      => ['stringValue' => 'wordpress'],
        ];
    }


    public function htmlToPlainText(string $html): string
    {
        if ($html === '') return '';

        // Drop script, style, and figure blocks with their contents
        $html = preg_replace('#<(script|style|figure)[^>]*>.*?</\1>#is', '', $html);

        // Drop images
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

    private function toIso(?string $date): string
    {
        if (!$date) return now()->toISOString();
        return \Carbon\Carbon::parse($date . ' UTC')->toISOString();
    }
}
