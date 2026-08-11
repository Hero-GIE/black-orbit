<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            $apiKey = env('FIREBASE_API_KEY');

            $response = Http::post(
                "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={$apiKey}",
                [
                    'email' => $request->email,
                    'password' => $request->password,
                    'returnSecureToken' => true,
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $firebaseUid = $data['localId'];
                $email = $data['email'];
                $token = $data['idToken'];

                $userData = $this->syncAndGetUser($firebaseUid, $email, $token);

                ActivityLogger::log('user_login', 'API Login', $email . ' authenticated via API', ['role' => $userData['role'] ?? 'student']);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'refresh_token' => $data['refreshToken'],
                        'user_id' => $data['localId'],
                        'email' => $data['email'],
                        'expires_in' => $data['expiresIn'],
                        'role' => $userData['role'] ?? 'student',
                        'username' => $userData['username'] ?? '',
                        'accesslevel' => $userData['accesslevel'] ?? 'user',
                        'interest' => $userData['interest'] ?? '',
                        'institution' => $userData['institution'] ?? '',
                        'field' => $userData['field'] ?? null,
                    ],
                    'message' => 'Login successful'
                ]);
            }

            $error = $response->json();
            $errorMessage = $error['error']['message'] ?? 'Invalid credentials';

            return response()->json([
                'success' => false,
                'error' => 'Login failed',
                'message' => $errorMessage
            ], 401);

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Login failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function syncAndGetUser($uid, $email, $token)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $isAdmin = ($email === 'admin@gmail.com');

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}");

            if (!$response->successful() || $response->status() === 404) {
                $userData = [
                    'fields' => [
                        'uid' => ['stringValue' => $uid],
                        'email' => ['stringValue' => $email],
                        'role' => ['stringValue' => $isAdmin ? 'admin' : 'student'],
                        'username' => ['stringValue' => $isAdmin ? 'Administrator' : ''],
                        'accesslevel' => ['stringValue' => $isAdmin ? 'admin' : 'user'],
                        'institution' => ['stringValue' => $isAdmin ? 'Admin Institution' : ''],
                        'interest' => ['stringValue' => ''],
                        'field' => ['nullValue' => null],
                        'created_at' => ['timestampValue' => now()->toISOString()],
                    ]
                ];

                Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                    ->patch("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}", $userData);

                Log::info("User document created for: {$email} with role: " . ($isAdmin ? 'admin' : 'student'));
                return $userData['fields'];
            }

            $data = $response->json();
            $fields = $data['fields'] ?? [];

            if ($isAdmin && ($fields['role']['stringValue'] ?? 'student') !== 'admin') {
                $updateData = [
                    'fields' => [
                        'role' => ['stringValue' => 'admin'],
                        'accesslevel' => ['stringValue' => 'admin'],
                        'username' => ['stringValue' => 'Administrator'],
                    ]
                ];

                Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                    ->patch("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}", $updateData);

                Log::info("Admin role updated for: {$email}");
                $fields['role']['stringValue'] = 'admin';
                $fields['accesslevel']['stringValue'] = 'admin';
                $fields['username']['stringValue'] = 'Administrator';
            }

            return [
                'role' => $fields['role']['stringValue'] ?? 'student',
                'username' => $fields['username']['stringValue'] ?? '',
                'email' => $fields['email']['stringValue'] ?? $email,
                'institution' => $fields['institution']['stringValue'] ?? '',
                'interest' => $fields['interest']['stringValue'] ?? '',
                'accesslevel' => $fields['accesslevel']['stringValue'] ?? 'user',
                'field' => $fields['field']['stringValue'] ?? null,
                'uid' => $fields['uid']['stringValue'] ?? $uid,
            ];

        } catch (\Exception $e) {
            Log::error('Firestore sync error: ' . $e->getMessage());
            return [
                'role' => 'student',
                'username' => '',
                'email' => $email,
                'institution' => '',
                'interest' => '',
                'accesslevel' => 'user',
                'field' => null,
                'uid' => $uid,
            ];
        }
    }

    public function getUserFromFirestore($uid)
    {
        try {
            $token = session('firebase_token');

            if (!$token) {
                Log::error('❌ No token available for getUserFromFirestore');
                return ['role' => 'student', 'username' => '', 'email' => '', 'institution' => '', 'interest' => '', 'accesslevel' => 'user', 'field' => null, 'uid' => $uid];
            }

            $projectId = env('FIREBASE_PROJECT_ID');

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}");

            if ($response->successful()) {
                $data = $response->json();
                $fields = $data['fields'] ?? [];
                return [
                    'role' => $fields['role']['stringValue'] ?? 'student',
                    'username' => $fields['username']['stringValue'] ?? '',
                    'email' => $fields['email']['stringValue'] ?? '',
                    'institution' => $fields['institution']['stringValue'] ?? '',
                    'interest' => $fields['interest']['stringValue'] ?? '',
                    'accesslevel' => $fields['accesslevel']['stringValue'] ?? 'user',
                    'field' => $fields['field']['stringValue'] ?? null,
                    'uid' => $fields['uid']['stringValue'] ?? $uid,
                ];
            }

            return ['role' => 'student', 'username' => '', 'email' => '', 'institution' => '', 'interest' => '', 'accesslevel' => 'user', 'field' => null, 'uid' => $uid];

        } catch (\Exception $e) {
            Log::error('Firestore fetch error: ' . $e->getMessage());
            return ['role' => 'student', 'username' => '', 'email' => '', 'institution' => '', 'interest' => '', 'accesslevel' => 'user', 'field' => null, 'uid' => $uid];
        }
    }

    public function syncUserToFirestore($uid, $email)
    {
        try {
            $apiKey = env('FIREBASE_API_KEY');
            $projectId = env('FIREBASE_PROJECT_ID');
            $isAdmin = ($email === 'admin@gmail.com');

            $response = Http::get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}?key={$apiKey}");

            if (!$response->successful() || $response->status() === 404) {
                $userData = [
                    'fields' => [
                        'uid' => ['stringValue' => $uid],
                        'email' => ['stringValue' => $email],
                        'role' => ['stringValue' => $isAdmin ? 'admin' : 'student'],
                        'username' => ['stringValue' => $isAdmin ? 'Administrator' : ''],
                        'accesslevel' => ['stringValue' => $isAdmin ? 'admin' : 'user'],
                        'institution' => ['stringValue' => $isAdmin ? 'Admin Institution' : ''],
                        'interest' => ['stringValue' => ''],
                        'field' => ['nullValue' => null],
                        'created_at' => ['timestampValue' => now()->toISOString()],
                    ]
                ];

                Http::patch("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$uid}?key={$apiKey}", $userData);
                Log::info("User document created for: {$email} with role: " . ($isAdmin ? 'admin' : 'student'));
            }
        } catch (\Exception $e) {
            Log::error('Firestore sync error: ' . $e->getMessage());
        }
    }
}
