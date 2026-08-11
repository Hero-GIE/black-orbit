<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ActivityLogger
{
    /**
     * Log an activity to Firestore
     */
    public static function log(string $type, string $title, string $description = '', array $metadata = [])
    {
        try {
            $projectId = env('FIREBASE_PROJECT_ID');
            $token = session('firebase_token');

            if (!$token || !$projectId) {
                return false;
            }

            $activityId = uniqid('act_', true);
            $now = now()->toIso8601ZuluString();
            $userId = session('firebase_user_id', 'system');
            $username = session('firebase_username', 'System');
            $email = session('firebase_email', '');

            // Build Firestore fields array
            $fields = [
                'activityId' => ['stringValue' => $activityId],
                'type' => ['stringValue' => $type],
                'title' => ['stringValue' => $title],
                'description' => ['stringValue' => $description],
                'userId' => ['stringValue' => $userId],
                'username' => ['stringValue' => $username],
                'email' => ['stringValue' => $email],
                'createdAt' => ['timestampValue' => $now],
                'isRead' => ['booleanValue' => false],
            ];

            // Add metadata as a map
            if (!empty($metadata)) {
                $metaFields = [];
                foreach ($metadata as $key => $value) {
                    if (is_bool($value)) {
                        $metaFields[$key] = ['booleanValue' => $value];
                    } elseif (is_int($value) || is_long($value)) {
                        $metaFields[$key] = ['integerValue' => $value];
                    } elseif (is_float($value) || is_double($value)) {
                        $metaFields[$key] = ['doubleValue' => $value];
                    } else {
                        $metaFields[$key] = ['stringValue' => (string) $value];
                    }
                }
                $fields['metadata'] = ['mapValue' => ['fields' => $metaFields]];
            }

            Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(10)->post(
                "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/activity_logs?documentId={$activityId}",
                [
                    'fields' => $fields,
                ]
            );

            return $activityId;

        } catch (\Exception $e) {
            Log::error('Failed to log activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pre-defined activity types with icons and colors
     */
    public static function getTypeInfo(string $type): array
    {
        return match($type) {
            'user_registered' => [
                'icon' => 'fas fa-user-plus',
                'color' => '#10b981',
                'bgColor' => '#d1fae5',
                'label' => 'New User',
            ],
            'user_login' => [
                'icon' => 'fas fa-sign-in-alt',
                'color' => '#3b82f6',
                'bgColor' => '#dbeafe',
                'label' => 'Login',
            ],
            'course_created' => [
                'icon' => 'fas fa-book',
                'color' => '#8b5cf6',
                'bgColor' => '#ede9fe',
                'label' => 'New Course',
            ],
            'course_updated' => [
                'icon' => 'fas fa-edit',
                'color' => '#f59e0b',
                'bgColor' => '#fef3c7',
                'label' => 'Course Updated',
            ],
            'enrollment' => [
                'icon' => 'fas fa-graduation-cap',
                'color' => '#06b6d4',
                'bgColor' => '#cffafe',
                'label' => 'Enrollment',
            ],
            'chat_created' => [
                'icon' => 'fas fa-comments',
                'color' => '#ec4899',
                'bgColor' => '#fce7f3',
                'label' => 'New Chat',
            ],
            'word_search' => [
                'icon' => 'fas fa-star',
                'color' => '#f97316',
                'bgColor' => '#ffedd5',
                'label' => 'Word Search',
            ],
            'achievement' => [
                'icon' => 'fas fa-trophy',
                'color' => '#eab308',
                'bgColor' => '#fef9c3',
                'label' => 'Achievement',
            ],
            'admin_action' => [
                'icon' => 'fas fa-shield-alt',
                'color' => '#ef4444',
                'bgColor' => '#fee2e2',
                'label' => 'Admin',
            ],
            default => [
                'icon' => 'fas fa-bell',
                'color' => '#6b7280',
                'bgColor' => '#f3f4f6',
                'label' => 'Activity',
            ],
        };
    }
}
