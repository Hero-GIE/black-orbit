<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PlayerController extends Controller
{
    public function index()
    {
        return view('admin.players.index');
    }

    private function getFirebaseToken()
    {
        return session('firebase_token');
    }

    public function fetchPlayers()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/players";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $players = [];

                foreach ($data['documents'] ?? [] as $doc) {
                    $fields = $doc['fields'] ?? [];
                    $players[] = $this->formatPlayerData(basename($doc['name']), $fields);
                }

                return response()->json(['success' => true, 'data' => $players]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to fetch players'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getPlayer($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/players/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if ($response->successful()) {
                $fields = $response->json()['fields'] ?? [];
                $player = $this->formatPlayerData($id, $fields);
                return response()->json(['success' => true, 'data' => $player]);
            }

            return response()->json(['success' => false, 'message' => 'Player not found'], 404);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'username' => 'required|string|max:255',
                'phase' => 'nullable|string',
                'xp' => 'nullable|integer',
                'destination' => 'nullable|string',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            // Get existing player data
            $getUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/players/{$id}";
            $getResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($getUrl);

            if (!$getResponse->successful()) {
                return response()->json(['success' => false, 'message' => 'Player not found'], 404);
            }

            $existingFields = $getResponse->json()['fields'] ?? [];

            // Build update fields
            $updateFields = [
                'username' => ['stringValue' => $request->username],
                'updatedAt' => ['timestampValue' => now()->toISOString()],
            ];

            // Update optional fields if provided
            if ($request->has('phase')) {
                $updateFields['phase'] = ['stringValue' => $request->phase];
            }
            if ($request->has('xp')) {
                $updateFields['xp'] = ['integerValue' => (int) $request->xp];
            }
            if ($request->has('destination')) {
                $updateFields['destination'] = ['stringValue' => $request->destination];
            }

            // Preserve existing fields
            $preserveFields = ['avatar', 'resources', 'crew', 'discoveries', 'achievements', 'learningUnlocked', 'builtModules', 'launchSite', 'spacecraft', 'user'];
            foreach ($preserveFields as $field) {
                if (isset($existingFields[$field])) {
                    $updateFields[$field] = $existingFields[$field];
                }
            }

            $patchUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/players/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->patch($patchUrl, ['fields' => $updateFields]);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Player updated: ' . $request->username,
                    'Player profile was modified by ' . session('firebase_username', 'Admin'),
                    ['playerId' => $id]
                );

                return response()->json(['success' => true, 'message' => 'Player updated successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to update player'], 500);

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

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/players/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->delete($url);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Player deleted',
                    'A player profile was removed by ' . session('firebase_username', 'Admin'),
                    ['playerId' => $id]
                );

                return response()->json(['success' => true, 'message' => 'Player deleted successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to delete player'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function formatPlayerData($id, $fields)
    {
        // Parse achievements
        $achievements = [];
        if (isset($fields['achievements']['arrayValue']['values'])) {
            foreach ($fields['achievements']['arrayValue']['values'] as $val) {
                $achievements[] = $val['stringValue'] ?? '';
            }
        }

        // Parse discoveries
        $discoveries = [];
        if (isset($fields['discoveries']['arrayValue']['values'])) {
            foreach ($fields['discoveries']['arrayValue']['values'] as $val) {
                $discoveries[] = $val['stringValue'] ?? '';
            }
        }

        // Parse built modules
        $builtModules = [];
        if (isset($fields['builtModules']['arrayValue']['values'])) {
            foreach ($fields['builtModules']['arrayValue']['values'] as $val) {
                $builtModules[] = $val['stringValue'] ?? '';
            }
        }

        // Parse crew
        $crew = [];
        if (isset($fields['crew']['arrayValue']['values'])) {
            foreach ($fields['crew']['arrayValue']['values'] as $crewMember) {
                $memberFields = $crewMember['mapValue']['fields'] ?? [];
                $crew[] = [
                    'id' => $memberFields['id']['stringValue'] ?? '',
                    'name' => $memberFields['name']['stringValue'] ?? '',
                    'role' => $memberFields['role']['stringValue'] ?? '',
                    'skillLevel' => $memberFields['skillLevel']['integerValue'] ?? 0,
                    'suitStyle' => $memberFields['suitStyle']['stringValue'] ?? '',
                ];
            }
        }

        // Parse learning unlocked
        $learningUnlocked = [];
        if (isset($fields['learningUnlocked']['mapValue']['fields'])) {
            $learningFields = $fields['learningUnlocked']['mapValue']['fields'];
            foreach ($learningFields as $key => $value) {
                $learningUnlocked[$key] = $value['booleanValue'] ?? false;
            }
        }

        // Parse resources
        $resources = [
            'energy' => $fields['resources']['mapValue']['fields']['energy']['integerValue'] ?? 0,
            'food' => $fields['resources']['mapValue']['fields']['food']['integerValue'] ?? 0,
            'water' => $fields['resources']['mapValue']['fields']['water']['integerValue'] ?? 0,
            'oxygen' => $fields['resources']['mapValue']['fields']['oxygen']['integerValue'] ?? 0,
            'materials' => $fields['resources']['mapValue']['fields']['materials']['integerValue'] ?? 0,
        ];

        // Parse launch site
        $launchSite = [];
        if (isset($fields['launchSite']['mapValue']['fields'])) {
            $siteFields = $fields['launchSite']['mapValue']['fields'];
            $launchSite = [
                'id' => $siteFields['id']['stringValue'] ?? '',
                'name' => $siteFields['name']['stringValue'] ?? '',
                'region' => $siteFields['region']['stringValue'] ?? '',
                'description' => $siteFields['description']['stringValue'] ?? '',
                'funFact' => $siteFields['funFact']['stringValue'] ?? '',
                'latitude' => $siteFields['latitude']['doubleValue'] ?? 0,
                'longitude' => $siteFields['longitude']['doubleValue'] ?? 0,
            ];
        }

        return [
            'id' => $id,
            'username' => $fields['username']['stringValue'] ?? 'Unknown',
            'avatar' => $fields['avatar']['stringValue'] ?? '',
            'phase' => $fields['phase']['stringValue'] ?? 'menu',
            'destination' => $fields['destination']['stringValue'] ?? 'moon',
            'xp' => $fields['xp']['integerValue'] ?? 0,
            'resources' => $resources,
            'crew' => $crew,
            'achievements' => $achievements,
            'discoveries' => $discoveries,
            'builtModules' => $builtModules,
            'learningUnlocked' => $learningUnlocked,
            'launchSite' => $launchSite,
            'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
            'updatedAt' => $fields['updatedAt']['timestampValue'] ?? '',
        ];
    }
}
