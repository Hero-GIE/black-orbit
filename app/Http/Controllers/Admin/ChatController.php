<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function index()
    {
        return view('admin.chats.index');
    }

    private function getFirebaseToken()
    {
        $token = session('firebase_token');
        if (empty($token)) {
            return null;
        }
        return $token;
    }

    public function fetchChats()
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/Chats";
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get($url);

            if ($response->status() === 404) {
                $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/chats";
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->get($url);
            }

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch chats'
                ], $response->status());
            }

            $data = $response->json();
            $documents = $data['documents'] ?? [];

            $chats = [];
            foreach ($documents as $doc) {
                $fields = $doc['fields'] ?? [];
                $chatId = basename($doc['name']);

                $users = [];
                $userDetails = [];

                if (isset($fields['users']['arrayValue'])) {
                    $values = $fields['users']['arrayValue']['values'] ?? [];
                    foreach ($values as $value) {
                        if (isset($value['mapValue'])) {
                            $userFields = $value['mapValue']['fields'] ?? [];
                            $user = [
                                'uid' => $userFields['uid']['stringValue'] ?? '',
                                'username' => $userFields['username']['stringValue'] ?? 'Unknown',
                                'email' => $userFields['email']['stringValue'] ?? '',
                                'role' => $userFields['role']['stringValue'] ?? 'student',
                                'accesslevel' => $userFields['accesslevel']['stringValue'] ?? 'user',
                                'interest' => $userFields['interest']['stringValue'] ?? '',
                                'institution' => $userFields['institution']['stringValue'] ?? '',
                                'createdAt' => $userFields['createdAt']['stringValue'] ?? $userFields['createdAt']['timestampValue'] ?? '',
                                'updatedAt' => $userFields['updatedAt']['stringValue'] ?? $userFields['updatedAt']['timestampValue'] ?? '',
                            ];
                            $users[] = $user['uid'];
                            $userDetails[] = $user;
                        } elseif (isset($value['stringValue'])) {
                            $userId = $value['stringValue'];
                            $users[] = $userId;
                            $userInfo = $this->getUserInfo($userId);
                            if ($userInfo) {
                                $userDetails[] = $userInfo;
                            }
                        }
                    }
                }
                $lastMessage = '';
                if (isset($fields['lastmessage']) && isset($fields['lastmessage']['mapValue'])) {
                    $lastMsgFields = $fields['lastmessage']['mapValue']['fields'] ?? [];
                    $lastMessage = $lastMsgFields['message']['stringValue'] ?? 'No messages yet';
                }

                $chats[] = [
                    'id' => $chatId,
                    'chatid' => $fields['chatid']['stringValue'] ?? $chatId,
                    'isGroup' => $fields['isGroup']['booleanValue'] ?? false,
                    'users' => $users,
                    'userDetails' => $userDetails,
                    'createdAt' => $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? '',
                    'updatedAt' => $fields['updatedAt']['timestampValue'] ?? $fields['updatedAt']['stringValue'] ?? '',
                    'lastMessage' => $lastMessage,
                ];
            }

            usort($chats, function($a, $b) {
                return strtotime($b['updatedAt']) - strtotime($a['updatedAt']);
            });

            return response()->json([
                'success' => true,
                'data' => $chats
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error fetching chats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching chats: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getChat($id)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated with Firebase'
                ], 401);
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/Chats/{$id}";
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get($url);

            if ($response->status() === 404) {
                $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/chats/{$id}";
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->get($url);
            }

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chat not found'
                ], 404);
            }

            $data = $response->json();
            $fields = $data['fields'] ?? [];
            $users = [];
            $userDetails = [];

            if (isset($fields['users']['arrayValue'])) {
                $values = $fields['users']['arrayValue']['values'] ?? [];
                foreach ($values as $value) {
                    if (isset($value['mapValue'])) {

                        $userFields = $value['mapValue']['fields'] ?? [];
                        $user = [
                            'uid' => $userFields['uid']['stringValue'] ?? '',
                            'username' => $userFields['username']['stringValue'] ?? 'Unknown',
                            'email' => $userFields['email']['stringValue'] ?? '',
                            'role' => $userFields['role']['stringValue'] ?? 'student',
                            'accesslevel' => $userFields['accesslevel']['stringValue'] ?? 'user',
                            'interest' => $userFields['interest']['stringValue'] ?? '',
                            'institution' => $userFields['institution']['stringValue'] ?? '',
                            'createdAt' => $userFields['createdAt']['stringValue'] ?? $userFields['createdAt']['timestampValue'] ?? '',
                            'updatedAt' => $userFields['updatedAt']['stringValue'] ?? $userFields['updatedAt']['timestampValue'] ?? '',
                        ];
                        $users[] = $user['uid'];
                        $userDetails[] = $user;
                    } elseif (isset($value['stringValue'])) {

                        $userId = $value['stringValue'];
                        $users[] = $userId;
                        $userInfo = $this->getUserInfo($userId);
                        if ($userInfo) {
                            $userDetails[] = $userInfo;
                        }
                    }
                }
            }

            // Fetch messages for this chat
            $messages = $this->getChatMessages($id);

            $chat = [
                'id' => $id,
                'chatid' => $fields['chatid']['stringValue'] ?? $id,
                'isGroup' => $fields['isGroup']['booleanValue'] ?? false,
                'users' => $users,
                'userDetails' => $userDetails,
                'messages' => $messages,
                'createdAt' => $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? '',
                'updatedAt' => $fields['updatedAt']['timestampValue'] ?? $fields['updatedAt']['stringValue'] ?? '',
                'lastMessage' => $messages ? end($messages)['message'] ?? '' : 'No messages',
            ];

            return response()->json([
                'success' => true,
                'data' => $chat
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error fetching chat: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching chat: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getChatMessages($chatId)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return [];
            }

            $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/Chats/{$chatId}/Messages";
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get($url);
            if ($response->status() === 404) {
                $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/Chats/{$chatId}/messages";
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->get($url);
            }

            if ($response->status() === 404) {
                $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/chats/{$chatId}/Messages";
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->get($url);
            }

            if ($response->status() === 404) {
                $url = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/chats/{$chatId}/messages";
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(30)->get($url);
            }

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $documents = $data['documents'] ?? [];

            $messages = [];
            foreach ($documents as $doc) {
                $fields = $doc['fields'] ?? [];

                $messageText = $fields['message']['stringValue'] ?? '';

                $messages[] = [
                    'id' => basename($doc['name']),
                    'messageid' => $fields['messageid']['stringValue'] ?? '',
                    'senderid' => $fields['senderid']['stringValue'] ?? '',
                    'senderName' => $this->getUserName($fields['senderid']['stringValue'] ?? ''),
                    'recepientid' => $fields['recepientid']['stringValue'] ?? '',
                    'message' => $messageText,
                    'messagetype' => $fields['messagetype']['stringValue'] ?? 'text',
                    'createdAt' => $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? '',
                ];
            }

            usort($messages, function($a, $b) {
                return strtotime($a['createdAt']) - strtotime($b['createdAt']);
            });

            return $messages;

        } catch (\Exception $e) {
            Log::error('❌ Error fetching messages: ' . $e->getMessage());
            return [];
        }
    }

    private function getUserName($userId)
    {
        if (empty($userId)) {
            return 'Unknown';
        }

        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return 'Unknown';
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get(
                "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$userId}"
            );

            if ($response->successful()) {
                $data = $response->json();
                $fields = $data['fields'] ?? [];
                return $fields['username']['stringValue'] ?? 'Unknown';
            }
        } catch (\Exception $e) {
            Log::error('❌ Error fetching user name: ' . $e->getMessage());
        }
        return 'Unknown';
    }

    private function getUserInfo($userId)
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = $this->getFirebaseToken();

            if (!$token) {
                return null;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(30)->get(
                "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/users/{$userId}"
            );

            if ($response->successful()) {
                $data = $response->json();
                $fields = $data['fields'] ?? [];
                return [
                    'uid' => $fields['uid']['stringValue'] ?? $userId,
                    'username' => $fields['username']['stringValue'] ?? 'Unknown',
                    'email' => $fields['email']['stringValue'] ?? '',
                    'role' => $fields['role']['stringValue'] ?? 'student',
                    'accesslevel' => $fields['accesslevel']['stringValue'] ?? 'user',
                    'interest' => $fields['interest']['stringValue'] ?? '',
                    'institution' => $fields['institution']['stringValue'] ?? '',
                    'createdAt' => $fields['createdAt']['timestampValue'] ?? $fields['createdAt']['stringValue'] ?? '',
                    'updatedAt' => $fields['updatedAt']['timestampValue'] ?? $fields['updatedAt']['stringValue'] ?? '',
                ];
            }
        } catch (\Exception $e) {
            Log::error('❌ Error fetching user info: ' . $e->getMessage());
        }
        return null;
    }
}
