<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseAuthService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('FIREBASE_API_KEY');

        if (!$this->apiKey) {
            Log::warning('FIREBASE_API_KEY not set in .env');
        }
    }

    /**
     * Verify Firebase JWT Token using REST API
     */
    public function verifyToken(string $token): ?array
    {

        if (app()->environment('local') && $token === 'test-token') {
            return [
                'uid' => 'test_user_' . time(),
                'email' => 'test@example.com',
                'name' => 'Test User',
                'picture' => '',
            ];
        }

        if (!$this->apiKey) {
            Log::error('Firebase API key is missing');

            // In local environment, return test user
            if (app()->environment('local')) {
                return [
                    'uid' => 'test_user_' . time(),
                    'email' => 'test@example.com',
                    'name' => 'Test User (No API Key)',
                ];
            }
            return null;
        }

        try {
            // Verify using Firebase API
            $response = Http::post(
                "https://identitytoolkit.googleapis.com/v1/accounts:lookup?key={$this->apiKey}",
                ['idToken' => $token]
            );

            if ($response->successful()) {
                $data = $response->json();
                $user = $data['users'][0] ?? null;

                if ($user) {
                    Log::info('Firebase token verified successfully', [
                        'uid' => $user['localId'],
                        'email' => $user['email'] ?? 'unknown'
                    ]);

                    return [
                        'uid' => $user['localId'],
                        'email' => $user['email'] ?? '',
                        'name' => $user['displayName'] ?? '',
                        'picture' => $user['photoUrl'] ?? '',
                        'email_verified' => $user['emailVerified'] ?? false,
                    ];
                }
            }

            Log::error('Firebase verification failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Firebase REST API error: ' . $e->getMessage());

            // In local environment, fallback to test user
            if (app()->environment('local')) {
                Log::info('Local environment - falling back to test user');
                return [
                    'uid' => 'test_user_' . time(),
                    'email' => 'test@example.com',
                    'name' => 'Test User (Fallback)',
                ];
            }

            return null;
        }
    }
}
