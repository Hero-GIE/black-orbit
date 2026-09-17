<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;

class FirebaseTokenService
{
    public function getToken(): string
    {
        $credentialsPath = storage_path('app/firebase/service-account.json');

        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException('Firebase service account JSON not found.');
        }

        $creds = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/datastore'],
            $credentialsPath
        );

        $token = $creds->fetchAuthToken();

        return $token['access_token'] ?? '';
    }
}
