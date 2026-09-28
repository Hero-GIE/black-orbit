<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FireStoreServiceForVideos
{
    protected string $projectId;

    public function __construct()
    {
        $this->projectId = env('FIREBASE_PROJECT_ID');
    }

    protected function token(): ?string
    {
        return session('firebase_token');
    }

    /**
     * Create a video document with an explicit ID.
     */
    public function createVideo(string $id, array $data): bool
    {
        $token = $this->token();
        if (!$token) throw new \Exception('Not authenticated');

        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/videos/{$id}";
        $fields = $this->toFirestoreFields($data);

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
            ->timeout(30)
            ->patch($url, ['fields' => $fields]);

        if (!$response->successful()) {
            throw new \Exception('Firestore create failed: ' . $response->body());
        }

        return true;
    }

    /**
     * Update only the `videourl` and `updatedAt` fields of an existing doc.
     */
    public function updateVideoUrl(string $id, string $videourl): bool
    {
        $token = $this->token();
        if (!$token) throw new \Exception('Not authenticated');

        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/videos/{$id}?updateMask.fieldPaths=videourl&updateMask.fieldPaths=updatedAt";

        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
            ->timeout(30)
            ->patch($url, [
                'fields' => [
                    'videourl'  => ['stringValue' => $videourl],
                    'updatedAt' => ['timestampValue' => now()->toISOString()],
                ]
            ]);

        if (!$response->successful()) {
            throw new \Exception('Firestore update failed: ' . $response->body());
        }

        return true;
    }

    /**
     * Fetch a video document by ID.
     */
    public function getVideo(string $id): ?array
    {
        $token = $this->token();
        if (!$token) throw new \Exception('Not authenticated');

        $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/videos/{$id}";
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

        if (!$response->successful()) return null;

        $fields = $response->json()['fields'] ?? [];
        return [
            'id'            => $id,
            'courseid'      => $fields['courseid']['stringValue'] ?? '',
            'lessonid'      => $fields['lessonid']['stringValue'] ?? '',
            'title'         => $fields['title']['stringValue'] ?? '',
            'description'   => $fields['description']['stringValue'] ?? '',
            'videourl'      => $fields['videourl']['stringValue'] ?? '',
            'prerequisites' => $this->pluckStringArray($fields['prerequisites'] ?? null),
            'resources'     => $this->pluckStringArray($fields['resources'] ?? null),
            'createdAt'     => $fields['createdAt']['timestampValue'] ?? '',
            'updatedAt'     => $fields['updatedAt']['timestampValue'] ?? '',
        ];
    }

    /**
     * Convert a plain PHP array to Firestore's field format.
     */
    protected function toFirestoreFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $fields[$key] = ['nullValue' => null];
            } elseif (is_bool($value)) {
                $fields[$key] = ['booleanValue' => $value];
            } elseif (is_int($value)) {
                $fields[$key] = ['integerValue' => (string) $value];
            } elseif (is_float($value)) {
                $fields[$key] = ['doubleValue' => $value];
            } elseif (is_array($value)) {
                $isList = array_keys($value) === range(0, count($value) - 1);
                if ($isList) {
                    $fields[$key] = [
                        'arrayValue' => [
                            'values' => array_map(fn($v) => $this->scalarToFirestore($v), $value),
                        ]
                    ];
                } else {
                    $fields[$key] = ['mapValue' => ['fields' => $this->toFirestoreFields($value)]];
                }
            } else {
                $fields[$key] = $this->scalarToFirestore($value);
            }
        }
        return $fields;
    }

    protected function scalarToFirestore($value): array
    {
        if (is_string($value)) return ['stringValue' => $value];
        if (is_int($value))    return ['integerValue' => (string) $value];
        if (is_float($value))  return ['doubleValue' => $value];
        if (is_bool($value))   return ['booleanValue' => $value];
        return ['nullValue' => null];
    }

    /**
     * Firestore string array -> PHP array of strings.
     */
    protected function pluckStringArray(?array $arrayValue): array
    {
        if (!$arrayValue || !isset($arrayValue['arrayValue']['values'])) return [];
        $out = [];
        foreach ($arrayValue['arrayValue']['values'] as $v) {
            if (isset($v['stringValue'])) $out[] = $v['stringValue'];
        }
        return $out;
    }
}
