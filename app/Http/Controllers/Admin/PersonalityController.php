<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PersonalityController extends Controller
{
    public function index()
    {
        return view('admin.personalities.index');
    }

    private function getFirebaseToken()
    {
        return session('firebase_token');
    }

    public function fetchPersonalities()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/personalities";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $personalities = [];

                foreach ($data['documents'] ?? [] as $doc) {
                    $fields = $doc['fields'] ?? [];

                    $achievements = [];
                    if (isset($fields['achievements']['arrayValue']['values'])) {
                        foreach ($fields['achievements']['arrayValue']['values'] as $val) {
                            $achievements[] = $val['stringValue'] ?? '';
                        }
                    }

                    $personalities[] = [
                        'id' => basename($doc['name']),
                        'name' => $fields['name']['stringValue'] ?? 'Unknown',
                        'occupation' => $fields['occupation']['stringValue'] ?? '',
                        'bio' => $fields['bio']['stringValue'] ?? '',
                        'image' => $fields['image']['stringValue'] ?? '',
                        'category' => $fields['category']['stringValue'] ?? '',
                        'achievements' => $achievements,
                        'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                    ];
                }

                return response()->json(['success' => true, 'data' => $personalities]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to fetch personalities'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getPersonality($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/personalities/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if ($response->successful()) {
                $fields = $response->json()['fields'] ?? [];

                $achievements = [];
                if (isset($fields['achievements']['arrayValue']['values'])) {
                    foreach ($fields['achievements']['arrayValue']['values'] as $val) {
                        $achievements[] = $val['stringValue'] ?? '';
                    }
                }

                $personality = [
                    'id' => $id,
                    'name' => $fields['name']['stringValue'] ?? 'Unknown',
                    'occupation' => $fields['occupation']['stringValue'] ?? '',
                    'bio' => $fields['bio']['stringValue'] ?? '',
                    'image' => $fields['image']['stringValue'] ?? '',
                    'category' => $fields['category']['stringValue'] ?? '',
                    'achievements' => $achievements,
                    'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                ];

                return response()->json(['success' => true, 'data' => $personality]);
            }

            return response()->json(['success' => false, 'message' => 'Personality not found'], 404);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'occupation' => 'nullable|string|max:255',
                'bio' => 'required|string',
                'image' => 'nullable|url',
                'category' => 'nullable|string|max:255',
                'achievements' => 'nullable|string',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $personalityId = $this->generateRandomId(28);

            $achievementsArray = [];
            if ($request->achievements) {
                if (strpos($request->achievements, "\n") !== false) {
                    $achievementsArray = array_filter(array_map('trim', explode("\n", $request->achievements)));
                } else {
                    $achievementsArray = array_filter(array_map('trim', explode(',', $request->achievements)));
                }
            }
            $achievementsValues = array_map(fn($val) => ['stringValue' => $val], $achievementsArray);

            $personalityData = [
                'fields' => [
                    'name' => ['stringValue' => $request->name],
                    'occupation' => ['stringValue' => $request->occupation ?? ''],
                    'bio' => ['stringValue' => $request->bio],
                    'image' => ['stringValue' => $request->image ?? ''],
                    'category' => ['stringValue' => $request->category ?? ''],
                    'achievements' => ['arrayValue' => ['values' => $achievementsValues]],
                    'createdAt' => ['timestampValue' => now()->toISOString()],
                    'updatedAt' => ['timestampValue' => now()->toISOString()],
                ]
            ];

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/personalities/{$personalityId}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->patch($url, $personalityData);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Personality added: ' . $request->name,
                    'A new personality profile was created by ' . session('firebase_username', 'Admin'),
                    ['personalityId' => $personalityId]
                );

                return response()->json(['success' => true, 'message' => 'Personality created successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to create personality'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'occupation' => 'nullable|string|max:255',
                'bio' => 'required|string',
                'image' => 'nullable|url',
                'category' => 'nullable|string|max:255',
                'achievements' => 'nullable|string',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $achievementsArray = [];
            if ($request->achievements) {
                if (strpos($request->achievements, "\n") !== false) {
                    $achievementsArray = array_filter(array_map('trim', explode("\n", $request->achievements)));
                } else {
                    $achievementsArray = array_filter(array_map('trim', explode(',', $request->achievements)));
                }
            }
            $achievementsValues = array_map(fn($val) => ['stringValue' => $val], $achievementsArray);

            // Get existing document to preserve createdAt
            $getUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/personalities/{$id}";
            $getResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($getUrl);
            $existingFields = $getResponse->successful() ? $getResponse->json()['fields'] ?? [] : [];

            $updateFields = [
                'name' => ['stringValue' => $request->name],
                'occupation' => ['stringValue' => $request->occupation ?? ''],
                'bio' => ['stringValue' => $request->bio],
                'image' => ['stringValue' => $request->image ?? ''],
                'category' => ['stringValue' => $request->category ?? ''],
                'achievements' => ['arrayValue' => ['values' => $achievementsValues]],
                'updatedAt' => ['timestampValue' => now()->toISOString()],
            ];

            if (isset($existingFields['createdAt'])) {
                $updateFields['createdAt'] = $existingFields['createdAt'];
            }

            $patchUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/personalities/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->patch($patchUrl, ['fields' => $updateFields]);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Personality updated: ' . $request->name,
                    'Personality profile was modified by ' . session('firebase_username', 'Admin'),
                    ['personalityId' => $id]
                );

                return response()->json(['success' => true, 'message' => 'Personality updated successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to update personality'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/personalities/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->delete($url);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Personality deleted',
                    'A personality profile was removed by ' . session('firebase_username', 'Admin'),
                    ['personalityId' => $id]
                );

                return response()->json(['success' => true, 'message' => 'Personality deleted successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to delete personality'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function generateRandomId($length = 28)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $id = '';
        for ($i = 0; $i < $length; $i++) {
            $id .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $id;
    }
}
