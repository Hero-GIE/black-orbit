<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FactController extends Controller
{
    public function index()
    {
        return view('admin.facts.index');
    }

    private function getFirebaseToken()
    {
        return session('firebase_token');
    }

    public function fetchFacts()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/facts";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $facts = [];

                foreach ($data['documents'] ?? [] as $doc) {
                    $fields = $doc['fields'] ?? [];

                    $facts[] = [
                        'id' => basename($doc['name']),
                        'author' => $fields['author']['stringValue'] ?? '',
                        'category' => $fields['category']['stringValue'] ?? '',
                        'description' => $fields['description']['stringValue'] ?? '',
                        'image' => $fields['image']['stringValue'] ?? '',
                        'title' => $fields['title']['stringValue'] ?? '',
                        'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                    ];
                }

                return response()->json(['success' => true, 'data' => $facts]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to fetch facts'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getFact($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/facts/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if ($response->successful()) {
                $fields = $response->json()['fields'] ?? [];

                $fact = [
                    'id' => $id,
                    'author' => $fields['author']['stringValue'] ?? '',
                    'category' => $fields['category']['stringValue'] ?? '',
                    'description' => $fields['description']['stringValue'] ?? '',
                    'image' => $fields['image']['stringValue'] ?? '',
                    'title' => $fields['title']['stringValue'] ?? '',
                    'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                ];

                return response()->json(['success' => true, 'data' => $fact]);
            }

            return response()->json(['success' => false, 'message' => 'Fact not found'], 404);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'category' => 'nullable|string|max:255',
                'author' => 'nullable|string|max:255',
                'description' => 'required|string',
                'image' => 'nullable|url',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $factId = $this->generateRandomId(28);

            $factData = [
                'fields' => [
                    'title' => ['stringValue' => $request->title],
                    'category' => ['stringValue' => $request->category ?? ''],
                    'author' => ['stringValue' => $request->author ?? ''],
                    'description' => ['stringValue' => $request->description],
                    'image' => ['stringValue' => $request->image ?? ''],
                    'createdAt' => ['timestampValue' => now()->toISOString()],
                    'updatedAt' => ['timestampValue' => now()->toISOString()],
                ]
            ];

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/facts/{$factId}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->patch($url, $factData);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Fact added: ' . $request->title,
                    'A new fact was created by ' . session('firebase_username', 'Admin'),
                    ['factId' => $factId]
                );

                return response()->json(['success' => true, 'message' => 'Fact created successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to create fact'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'category' => 'nullable|string|max:255',
                'author' => 'nullable|string|max:255',
                'description' => 'required|string',
                'image' => 'nullable|url',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $getUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/facts/{$id}";
            $getResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($getUrl);
            $existingFields = $getResponse->successful() ? $getResponse->json()['fields'] ?? [] : [];

            $updateFields = [
                'title' => ['stringValue' => $request->title],
                'category' => ['stringValue' => $request->category ?? ''],
                'author' => ['stringValue' => $request->author ?? ''],
                'description' => ['stringValue' => $request->description],
                'image' => ['stringValue' => $request->image ?? ''],
                'updatedAt' => ['timestampValue' => now()->toISOString()],
            ];

            if (isset($existingFields['createdAt'])) {
                $updateFields['createdAt'] = $existingFields['createdAt'];
            }

            $patchUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/facts/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->patch($patchUrl, ['fields' => $updateFields]);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Fact updated: ' . $request->title,
                    'Fact was modified by ' . session('firebase_username', 'Admin'),
                    ['factId' => $id]
                );

                return response()->json(['success' => true, 'message' => 'Fact updated successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to update fact'], 500);

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

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/facts/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->delete($url);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Fact deleted',
                    'A fact was removed by ' . session('firebase_username', 'Admin'),
                    ['factId' => $id]
                );

                return response()->json(['success' => true, 'message' => 'Fact deleted successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to delete fact'], 500);

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
