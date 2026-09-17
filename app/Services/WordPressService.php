<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WordPressService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('WORDPRESS_BASE_URL'), '/');
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
        $contentText = trim(strip_tags($contentHtml));
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
            'content'     => ['stringValue' => $contentHtml],
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

    private function toIso(?string $date): string
    {
        if (!$date) return now()->toISOString();
        return \Carbon\Carbon::parse($date . ' UTC')->toISOString();
    }
}
