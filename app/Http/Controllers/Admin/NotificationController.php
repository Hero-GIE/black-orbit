<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NotificationController extends Controller
{
    public function index()
    {
        return view('admin.notifications.index');
    }

    private function getFirebaseToken()
    {
        return session('firebase_token');
    }

    public function fetchNotifications()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/notifications";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $notifications = [];

                foreach ($data['documents'] ?? [] as $doc) {
                    $fields = $doc['fields'] ?? [];
                    $notifications[] = [
                        'id' => basename($doc['name']),
                        'title' => $fields['title']['stringValue'] ?? '',
                        'message' => $fields['message']['stringValue'] ?? '',
                        'image' => $fields['image']['stringValue'] ?? '',
                        'isRead' => $fields['isRead']['booleanValue'] ?? false,
                        'route' => $fields['route']['stringValue'] ?? '',
                        'userid' => $fields['userid']['stringValue'] ?? '',
                        'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                    ];
                }

                return response()->json(['success' => true, 'data' => $notifications]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to fetch notifications'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getNotification($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/notifications/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($url);

            if ($response->successful()) {
                $fields = $response->json()['fields'] ?? [];
                $notification = [
                    'id' => $id,
                    'title' => $fields['title']['stringValue'] ?? '',
                    'message' => $fields['message']['stringValue'] ?? '',
                    'image' => $fields['image']['stringValue'] ?? '',
                    'isRead' => $fields['isRead']['booleanValue'] ?? false,
                    'route' => $fields['route']['stringValue'] ?? '',
                    'userid' => $fields['userid']['stringValue'] ?? '',
                    'createdAt' => $fields['createdAt']['timestampValue'] ?? '',
                ];

                return response()->json(['success' => true, 'data' => $notification]);
            }

            return response()->json(['success' => false, 'message' => 'Notification not found'], 404);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'image' => 'nullable|url',
                'route' => 'nullable|string|max:255',
                'userid' => 'nullable|string|max:255',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $notifId = $this->generateRandomId(20);
            $targetUser = $request->userid ?? 'all';

            $notifData = [
                'fields' => [
                    'id' => ['stringValue' => $notifId],
                    'title' => ['stringValue' => $request->title],
                    'message' => ['stringValue' => $request->message],
                    'image' => ['stringValue' => $request->image ?? ''],
                    'isRead' => ['booleanValue' => false],
                    'route' => ['stringValue' => $request->route ?? ''],
                    'userid' => ['stringValue' => $targetUser],
                    'createdAt' => ['timestampValue' => now()->toISOString()],
                ]
            ];

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/notifications/{$notifId}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->patch($url, $notifData);

            if ($response->successful()) {
                // Send Push Notification via OneSignal
                $this->sendOneSignalPush($request->title, $request->message, $request->image, $targetUser);

                ActivityLogger::log(
                    'admin_action',
                    'Notification sent: ' . $request->title,
                    'A push notification was sent by ' . session('firebase_username', 'Admin'),
                    ['notificationId' => $notifId]
                );

                return response()->json(['success' => true, 'message' => 'Notification sent successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to send notification'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'image' => 'nullable|url',
                'route' => 'nullable|string|max:255',
                'userid' => 'nullable|string|max:255',
            ]);

            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
            }

            $getUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/notifications/{$id}";
            $getResponse = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->get($getUrl);
            $existingFields = $getResponse->successful() ? $getResponse->json()['fields'] ?? [] : [];

            $updateFields = [
                'id' => ['stringValue' => $id],
                'title' => ['stringValue' => $request->title],
                'message' => ['stringValue' => $request->message],
                'image' => ['stringValue' => $request->image ?? ''],
                'route' => ['stringValue' => $request->route ?? ''],
                'userid' => ['stringValue' => $request->userid ?? 'all'],
            ];

            if (isset($existingFields['createdAt'])) {
                $updateFields['createdAt'] = $existingFields['createdAt'];
            } else {
                $updateFields['createdAt'] = ['timestampValue' => now()->toISOString()];
            }

            if (isset($existingFields['isRead'])) {
                $updateFields['isRead'] = $existingFields['isRead'];
            } else {
                $updateFields['isRead'] = ['booleanValue' => false];
            }

            $patchUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/notifications/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->patch($patchUrl, ['fields' => $updateFields]);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Notification updated: ' . $request->title,
                    'Notification was modified by ' . session('firebase_username', 'Admin'),
                    ['notificationId' => $id]
                );

                return response()->json(['success' => true, 'message' => 'Notification updated successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to update notification'], 500);

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

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/notifications/{$id}";
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->timeout(30)->delete($url);

            if ($response->successful()) {
                ActivityLogger::log(
                    'admin_action',
                    'Notification deleted',
                    'A notification was removed by ' . session('firebase_username', 'Admin'),
                    ['notificationId' => $id]
                );

                return response()->json(['success' => true, 'message' => 'Notification deleted successfully']);
            }

            return response()->json(['success' => false, 'message' => 'Failed to delete notification'], 500);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function sendOneSignalPush($title, $message, $image = null, $userid = 'all')
    {
        try {
            $appId = env('ONESIGNAL_APP_ID');
            $restApiKey = env('ONESIGNAL_REST_API_KEY');

            if (!$appId || !$restApiKey) {
                return;
            }

            $payload = [
                'app_id' => $appId,
                'headings' => ['en' => $title],
                'contents' => ['en' => $message],
            ];

            if ($image) {
                $payload['big_picture'] = $image;
                $payload['ios_attachments'] = ['id' => $image];
            }

            if ($userid && $userid !== 'all') {
                $payload['include_external_user_ids'] = [$userid];
            } else {

                $payload['included_segments'] = ['Subscribed Users'];
            }

            Http::withHeaders([
                'Authorization' => 'Basic ' . $restApiKey,
                'Content-Type' => 'application/json'
            ])->post('https://onesignal.com/api/v1/notifications', $payload);

        } catch (\Exception $e) {
        }
    }

    private function generateRandomId($length = 20)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $id = '';
        for ($i = 0; $i < $length; $i++) {
            $id .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $id;
    }
}
