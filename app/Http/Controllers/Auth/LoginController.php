<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\ConnectException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('session.login-session');
    }

    public function login(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            // Get API Key
            $apiKey = env('FIREBASE_API_KEY');
            
            if (empty($apiKey)) {
                Log::error('FIREBASE_API_KEY not set');
                return $this->handleErrorResponse($request, 'Authentication service not configured. Please contact administrator.');
            }

            // Build the Firebase URL
            $firebaseUrl = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={$apiKey}";

            // Make the HTTP request
            $response = Http::timeout(15)
                ->withOptions(['connect_timeout' => 10])
                ->post($firebaseUrl, [
                    'email' => $request->email,
                    'password' => $request->password,
                    'returnSecureToken' => true,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $uid = $data['localId'];
                $email = $data['email'];

                // Get user from Firestore
                $authController = app(AuthController::class);
                $userData = $authController->getUserFromFirestore($uid);

                $isAdmin = ($email === 'admin@gmail.com');

                if ($isAdmin) {
                    $userData['role'] = 'admin';
                    $userData['accesslevel'] = 'admin';
                    $userData['username'] = 'Administrator';
                    $authController->syncUserToFirestore($uid, $email);
                }

                // Store session data
                Session::put('firebase_token', $data['idToken']);
                Session::put('firebase_refresh_token', $data['refreshToken']);
                Session::put('firebase_user_id', $uid);
                Session::put('firebase_email', $email);
                Session::put('firebase_role', $userData['role'] ?? ($isAdmin ? 'admin' : 'student'));
                Session::put('firebase_accesslevel', $userData['accesslevel'] ?? ($isAdmin ? 'admin' : 'user'));
                Session::put('firebase_username', $userData['username'] ?? ($isAdmin ? 'Administrator' : ''));

                // Log activity
                ActivityLogger::log('user_login', 'User logged in', $email . ' signed in successfully', ['ip' => $request->ip()]);

                // Check if request expects JSON (API call)
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Login successful',
                        'data' => [
                            'user' => [
                                'id' => $uid,
                                'email' => $email,
                                'role' => $userData['role'] ?? ($isAdmin ? 'admin' : 'student'),
                                'accesslevel' => $userData['accesslevel'] ?? ($isAdmin ? 'admin' : 'user'),
                                'username' => $userData['username'] ?? ($isAdmin ? 'Administrator' : ''),
                            ],
                            'token' => $data['idToken'],
                            'refresh_token' => $data['refreshToken'],
                            'expires_in' => $data['expiresIn'] ?? null
                        ]
                    ], 200);
                }

                // For web requests, redirect to dashboard
                return redirect()->route('admin.dashboard')->with('success', 'Welcome back, ' . ($userData['username'] ?? 'Administrator') . '!');
            }

            // Handle unsuccessful response
            $error = $response->json();
            $errorMessage = $error['error']['message'] ?? 'Invalid credentials';

            // Check for specific Firebase error codes
            $firebaseErrorCodes = [
                'INVALID_LOGIN_CREDENTIALS' => 'Invalid email or password. Please try again.',
                'EMAIL_NOT_FOUND' => 'No account found with this email address.',
                'INVALID_PASSWORD' => 'Incorrect password. Please try again.',
                'USER_DISABLED' => 'This account has been disabled. Contact support.',
                'TOO_MANY_ATTEMPTS_TRY_LATER' => 'Too many failed attempts. Please wait a moment and try again.',
                'API_KEY_NOT_VALID' => 'API key is not valid. Please check your Firebase configuration.',
                'INVALID_API_KEY' => 'Invalid API key. Please check your Firebase configuration.',
                'MISSING_API_KEY' => 'API key is missing. Please check your Firebase configuration.',
            ];

            $friendlyMessage = $firebaseErrorCodes[$errorMessage] ?? $errorMessage;
            Log::warning('Login failed: ' . $friendlyMessage);

            return $this->handleErrorResponse($request, $friendlyMessage);

        } catch (ConnectException $e) {
            Log::error('Network error: ' . $e->getMessage());
            return $this->handleErrorResponse($request, 'Unable to connect to the login server. Please check your internet connection and try again.');

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return $this->handleErrorResponse($request, 'Something went wrong during login. Please try again.');
        }
    }

    /**
     * Handle error response for both web and API requests
     */
    private function handleErrorResponse(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 401);
        }

        return back()->withErrors(['email' => $message])->withInput();
    }

    public function logout(Request $request)
    {
        ActivityLogger::log('admin_action', 'User logged out', session('firebase_email') . ' signed out');

        Session::forget([
            'firebase_token',
            'firebase_refresh_token',
            'firebase_user_id',
            'firebase_email',
            'firebase_role',
            'firebase_accesslevel',
            'firebase_username'
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Handle API logout
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ], 200);
        }

        return redirect('/login')->with('success', 'Logged out successfully');
    }
}