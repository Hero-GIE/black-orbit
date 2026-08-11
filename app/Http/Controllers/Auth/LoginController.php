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
    private function getNetworkErrorMessage(string $error): ?string
    {
        if (preg_match('/cURL error (\d+):/', $error, $matches)) {
            $code = (int) $matches[1];
            return match($code) {
                6  => 'Unable to connect to the authentication server. Please check your internet connection.',
                7  => 'Could not reach the login server. Please check your internet connection and try again.',
                28 => 'The login request took too long. The server might be slow — please try again.',
                35 => 'Secure connection failed. There may be a network interference or firewall blocking the connection.',
                52 => 'The server returned an empty response. Please try again.',
                56 => 'Connection was reset unexpectedly. Please check your network and try again.',
                default => null,
            };
        }

        if (str_contains($error, 'Connection refused') ||
            str_contains($error, 'getaddrinfo failed') ||
            str_contains($error, 'Network is unreachable') ||
            str_contains($error, 'No route to host')) {
            return 'Unable to connect to the authentication server. Please check your internet connection.';
        }

        return null;
    }

    public function showLoginForm()
    {
        return view('session.login-session');
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            $apiKey = env('FIREBASE_API_KEY');

            $response = Http::timeout(15)
                ->withOptions(['connect_timeout' => 10])
                ->post(
                    "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={$apiKey}",
                    [
                        'email' => $request->email,
                        'password' => $request->password,
                        'returnSecureToken' => true,
                    ]
                );

            if ($response->successful()) {
                $data = $response->json();
                $uid = $data['localId'];
                $email = $data['email'];

                $authController = app(AuthController::class);
                $userData = $authController->getUserFromFirestore($uid);
                $isAdmin = ($email === 'admin@gmail.com');

                if ($isAdmin) {
                    $userData['role'] = 'admin';
                    $userData['accesslevel'] = 'admin';
                    $userData['username'] = 'Administrator';
                    $authController->syncUserToFirestore($uid, $email);
                    Log::info('Admin login detected, role set to admin for: ' . $email);
                }

                Session::put('firebase_token', $data['idToken']);
                Session::put('firebase_refresh_token', $data['refreshToken']);
                Session::put('firebase_user_id', $uid);
                Session::put('firebase_email', $email);
                Session::put('firebase_role', $userData['role'] ?? ($isAdmin ? 'admin' : 'student'));
                Session::put('firebase_accesslevel', $userData['accesslevel'] ?? ($isAdmin ? 'admin' : 'user'));
                Session::put('firebase_username', $userData['username'] ?? ($isAdmin ? 'Administrator' : ''));

                Log::info('Session stored:', [
                    'role' => session('firebase_role'),
                    'accesslevel' => session('firebase_accesslevel'),
                    'username' => session('firebase_username'),
                ]);

                ActivityLogger::log('user_login', 'User logged in', $email . ' signed in successfully', ['ip' => $request->ip()]);

                return redirect()->route('admin.dashboard')->with('success', 'Welcome back, ' . ($userData['username'] ?? 'Administrator') . '!');
            }

            $error = $response->json();
            $errorMessage = $error['error']['message'] ?? 'Invalid credentials';
            $friendlyMessage = match($errorMessage) {
                'INVALID_LOGIN_CREDENTIALS'    => 'Invalid email or password. Please try again.',
                'EMAIL_NOT_FOUND'              => 'No account found with this email address.',
                'INVALID_PASSWORD'             => 'Incorrect password. Please try again.',
                'USER_DISABLED'                => 'This account has been disabled. Contact support.',
                'TOO_MANY_ATTEMPTS_TRY_LATER'  => 'Too many failed attempts. Please wait a moment and try again.',
                default                        => $errorMessage,
            };

            return back()->withErrors(['email' => $friendlyMessage])->withInput();

        } catch (ConnectException $e) {
            Log::error('Login network error: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Unable to connect to the login server. Please check your internet connection and try again.'])->withInput();

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $networkMessage = $this->getNetworkErrorMessage($errorMessage);

            if ($networkMessage) {
                Log::error('Login network error: ' . $errorMessage);
                return back()->withErrors(['email' => $networkMessage])->withInput();
            }

            Log::error('Login error: ' . $errorMessage);
            return back()->withErrors(['email' => 'Something went wrong during login. Please try again.'])->withInput();
        }
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

        return redirect('/login')->with('success', 'Logged out successfully');
    }
}
