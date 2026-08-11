<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index');
    }

    private function getFirebaseToken()
    {
        $token = session('firebase_token');
        if (empty($token)) {
            return null;
        }
        return $token;
    }

    public function fetchUsers()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated with Firebase'], 401);
            }

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users");

            if ($response->successful()) {
                $data = $response->json();
                $users = [];

                foreach ($data['documents'] ?? [] as $doc) {
                    $fields = $doc['fields'] ?? [];
                    $users[] = [
                        'id' => basename($doc['name']),
                        'uid' => $fields['uid']['stringValue'] ?? '',
                        'username' => $fields['username']['stringValue'] ?? '',
                        'email' => $fields['email']['stringValue'] ?? '',
                        'role' => $fields['role']['stringValue'] ?? 'student',
                        'accesslevel' => $fields['accesslevel']['stringValue'] ?? 'user',
                        'interest' => $fields['interest']['stringValue'] ?? '',
                        'institution' => $fields['institution']['stringValue'] ?? '',
                        'enrolledCourses' => $fields['enrolledCourses']['arrayValue']['values'] ?? [],
                        'createdAt' => $fields['createdAt']['stringValue'] ?? $fields['createdAt']['timestampValue'] ?? '',
                        'updatedAt' => $fields['updatedAt']['stringValue'] ?? $fields['updatedAt']['timestampValue'] ?? '',
                    ];
                }

                return response()->json(['success' => true, 'data' => $users]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to fetch users'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error fetching users: ' . $e->getMessage()], 500);
        }
    }

    public function getUser($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated with Firebase'], 401);
            }

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$id}");

            if ($response->successful()) {
                $data = $response->json();
                $fields = $data['fields'] ?? [];

                $user = [
                    'id' => $id,
                    'uid' => $fields['uid']['stringValue'] ?? '',
                    'username' => $fields['username']['stringValue'] ?? '',
                    'email' => $fields['email']['stringValue'] ?? '',
                    'role' => $fields['role']['stringValue'] ?? 'student',
                    'accesslevel' => $fields['accesslevel']['stringValue'] ?? 'user',
                    'interest' => $fields['interest']['stringValue'] ?? '',
                    'institution' => $fields['institution']['stringValue'] ?? '',
                    'createdAt' => $fields['createdAt']['stringValue'] ?? $fields['createdAt']['timestampValue'] ?? '',
                    'updatedAt' => $fields['updatedAt']['stringValue'] ?? $fields['updatedAt']['timestampValue'] ?? '',
                ];

                return response()->json(['success' => true, 'data' => $user]);
            }

            return response()->json(['success' => false, 'message' => 'User not found'], 404);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error fetching user: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string|max:255',
                'email' => 'required|email',
                'role' => 'required|string',
                'accesslevel' => 'required|string',
                'interest' => 'nullable|string',
                'institution' => 'nullable|string',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated with Firebase'], 401);
            }

            $userId = uniqid() . '_' . time();

            $userData = [
                'fields' => [
                    'uid' => ['stringValue' => $userId],
                    'username' => ['stringValue' => $request->username],
                    'email' => ['stringValue' => $request->email],
                    'role' => ['stringValue' => $request->role],
                    'accesslevel' => ['stringValue' => $request->accesslevel],
                    'interest' => ['stringValue' => $request->interest ?? ''],
                    'institution' => ['stringValue' => $request->institution ?? ''],
                    'field' => ['nullValue' => null],
                    'enrolledCourses' => ['arrayValue' => ['values' => []]],
                    'createdAt' => ['stringValue' => now()->toISOString()],
                    'updatedAt' => ['stringValue' => now()->toISOString()],
                ]
            ];

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->patch("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$userId}", $userData);

            if ($response->successful()) {
                ActivityLogger::log('user_registered', 'New user created', $request->username . ' (' . $request->email . ') was added', ['role' => $request->role]);

                return response()->json(['success' => true, 'message' => 'User created successfully', 'data' => ['id' => $userId]]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to create user'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error creating user: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'username' => 'required|string|max:255',
                'email' => 'required|email',
                'role' => 'required|string',
                'accesslevel' => 'required|string',
                'interest' => 'nullable|string',
                'institution' => 'nullable|string',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated with Firebase'], 401);
            }

            $getResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->get("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$id}");

            if (!$getResponse->successful()) {
                return response()->json(['success' => false, 'message' => 'User not found'], 404);
            }

            $existingData = $getResponse->json();
            $existingFields = $existingData['fields'] ?? [];

            $updateFields = [
                'username' => ['stringValue' => $request->username],
                'email' => ['stringValue' => $request->email],
                'role' => ['stringValue' => $request->role],
                'accesslevel' => ['stringValue' => $request->accesslevel],
                'interest' => ['stringValue' => $request->interest ?? ''],
                'institution' => ['stringValue' => $request->institution ?? ''],
                'updatedAt' => ['stringValue' => now()->toISOString()],
            ];

            $preserveFields = ['uid', 'field', 'createdAt'];
            foreach ($preserveFields as $field) {
                if (isset($existingFields[$field])) {
                    $updateFields[$field] = $existingFields[$field];
                }
            }

            $existingEnrolled = $existingFields['enrolledCourses']['arrayValue']['values'] ?? [];
            $updateFields['enrolledCourses'] = ['arrayValue' => ['values' => $existingEnrolled]];

            $userData = ['fields' => $updateFields];

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->patch("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$id}", $userData);

            if ($response->successful()) {
                ActivityLogger::log('user_registered', 'User updated', $request->username . '\'s profile was updated', ['role' => $request->role]);

                return response()->json(['success' => true, 'message' => 'User updated successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to update user'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error updating user: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated with Firebase'], 401);
            }

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])
                ->delete("https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$id}");

            if ($response->successful()) {
                ActivityLogger::log('admin_action', 'User deleted', "User {$id} was removed from the system");

                return response()->json(['success' => true, 'message' => 'User deleted successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to delete user'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()], 500);
        }
    }
}
