<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FirestoreServiceForVideos
{
    protected string $projectId;
    protected ?string $credentialsFile;

    public function __construct()
    {
        $this->projectId       = env('FIREBASE_PROJECT_ID');
        $this->credentialsFile = env('FIREBASE_CREDENTIALS');
    }

    /**
     * Get an auth token — service account preferred, session fallback.
     */
  protected function token(): ?string
{
    if ($this->credentialsFile && file_exists($this->credentialsFile)) {
        $cached = Cache::get('firestore_service_token');
        if ($cached) {
            return $cached;
        }

        try {
            $token = $this->getServiceAccountToken();
            Cache::put('firestore_service_token', $token, now()->addMinutes(50));
            Log::info('[firestore:token] service account token acquired');
            return $token;
        } catch (\Throwable $e) {
            Log::error('[firestore:token] service account token failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    $sessionToken = session('firebase_token');
    if ($sessionToken) {
        Log::warning('[firestore:token] using session token fallback');
        return $sessionToken;
    }

    Log::error('[firestore:token] no token available', [
        'credentials_file' => $this->credentialsFile,
        'file_exists'      => $this->credentialsFile ? file_exists($this->credentialsFile) : false,
    ]);

    return null;
}

    /**
     * Exchange the service account JSON for an OAuth access token.
     */
    protected function getServiceAccountToken(): string
    {
        $creds = json_decode(file_get_contents($this->credentialsFile), true);
        if (!$creds) {
            throw new \Exception("Invalid credentials JSON");
        }

        $b64 = fn($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

        $header  = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $now     = time();
        $payload = $b64(json_encode([
            'iss'   => $creds['client_email'],
            'sub'   => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $signature = '';
        if (!openssl_sign($header . '.' . $payload, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new \Exception("Failed to sign JWT");
        }

        $jwt = $header . '.' . $payload . '.' . $b64($signature);

        $response = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Token exchange failed: " . $response->body());
        }

        $accessToken = $response->json('access_token');
        if (!$accessToken) {
            throw new \Exception("No access token in response");
        }

        return $accessToken;
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

    Log::info('[firestore:createVideo] writing', [
        'project' => $this->projectId,
        'docId'   => $id,
        'url'     => $url,
        'field_count' => count($fields),
    ]);

    $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
        ->timeout(30)
        ->patch($url, ['fields' => $fields]);

    if (!$response->successful()) {
        Log::error('[firestore:createVideo] FAILED', [
            'status' => $response->status(),
            'body'   => substr($response->body(), 0, 500),
            'docId'  => $id,
        ]);
        throw new \Exception('Firestore create failed: ' . $response->body());
    }

    Log::info('[firestore:createVideo] OK', [
        'docId' => $id,
        'name'  => $response->json('name'),
    ]);

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
                $isList = empty($value) || array_keys($value) === range(0, count($value) - 1);
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
