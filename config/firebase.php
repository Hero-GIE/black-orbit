<?php

return [
    'firestore' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'key_file' => storage_path('app/firebase/service-account.json'),
    ],
];
