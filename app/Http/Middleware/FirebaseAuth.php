<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\FirebaseAuthService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class FirebaseAuth
{
    protected FirebaseAuthService $firebaseAuth;

    public function __construct(FirebaseAuthService $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    public function handle(Request $request, Closure $next)
    {
        $token = Session::get('firebase_token');
        $environment = app()->environment();

        Log::info('FirebaseAuth middleware', [
            'has_token'   => !empty($token),
            'environment' => $environment,
            'path'        => $request->path(),
            'method'      => $request->method(),
        ]);

        // Local dev fallback (no token required)
        if ($environment === 'local' && !$token) {
            Log::info('Local environment - using test user (no token)');
            $request->merge([
                'firebase_user' => [
                    'uid'     => 'test_user_' . time(),
                    'email'   => 'test@example.com',
                    'name'    => 'Test User',
                    'picture' => '',
                ],
                'user_id' => 'test_user_' . time(),
            ]);
            return $next($request);
        }

        if (!$token) {
            return $this->logoutAndRedirect('Please login to continue.');
        }

        $userData = $this->firebaseAuth->verifyToken($token);

        if (!$userData) {
            Log::warning('Firebase token expired — logging out', [
                'environment'   => $environment,
                'token_preview' => substr($token, 0, 30) . '...',
            ]);

            return $this->logoutAndRedirect('Your session has expired. Please login again.');
        }

        $request->merge([
            'firebase_user' => $userData,
            'user_id'       => $userData['uid'],
        ]);

        Log::info('Authentication successful', [
            'uid'         => $userData['uid'],
            'email'       => $userData['email'] ?? 'unknown',
            'environment' => $environment,
        ]);

        return $next($request);
    }

    /**
     * Wipe the session and redirect to login.
     */
    private function logoutAndRedirect(string $message)
    {
        Session::forget([
            'firebase_token',
            'firebase_refresh_token',
            'firebase_user_id',
            'firebase_email',
            'firebase_role',
            'firebase_accesslevel',
            'firebase_username',
        ]);

        return redirect('/login')->with('error', $message);
    }
}
