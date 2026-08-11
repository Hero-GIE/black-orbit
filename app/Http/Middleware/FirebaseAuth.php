<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FirebaseAuthService;
use Illuminate\Support\Facades\Log;

class FirebaseAuth
{
    protected FirebaseAuthService $firebaseAuth;

    public function __construct(FirebaseAuthService $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $environment = app()->environment();

        // Log the request for debugging
        Log::info('FirebaseAuth middleware', [
            'has_token' => !empty($token),
            'environment' => $environment,
            'path' => $request->path(),
            'method' => $request->method()
        ]);

        // LOCAL: Allow requests without token for testing
        if ($environment === 'local' && !$token) {
            Log::info('Local environment - using test user (no token)');
            $request->merge([
                'firebase_user' => [
                    'uid' => 'test_user_' . time(),
                    'email' => 'test@example.com',
                    'name' => 'Test User',
                    'picture' => '',
                ],
                'user_id' => 'test_user_' . time()
            ]);
            return $next($request);
        }

        // PRODUCTION OR TOKEN REQUIRED: Require token
        if (!$token) {
            Log::warning('No token provided', ['environment' => $environment]);
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'No token provided. Please include Firebase JWT token.'
            ], 401);
        }

        // Verify the token
        $userData = $this->firebaseAuth->verifyToken($token);

        if (!$userData) {
            Log::warning('Invalid or expired token', [
                'environment' => $environment,
                'token_preview' => substr($token, 0, 30) . '...'
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Invalid or expired token. Please login again.'
            ], 401);
        }

        // Attach user data to request
        $request->merge([
            'firebase_user' => $userData,
            'user_id' => $userData['uid'],
        ]);

        Log::info('Authentication successful', [
            'uid' => $userData['uid'],
            'email' => $userData['email'] ?? 'unknown',
            'environment' => $environment
        ]);

        return $next($request);
    }
}
