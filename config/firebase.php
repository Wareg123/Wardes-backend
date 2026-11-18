<?php

return [
    'default' => env('FIREBASE_PROJECT_ID', 'app'),
    'projects' => [
        'app' => [
            'credentials' => env('FIREBASE_CREDENTIALS'),
            'project_id' => env('FIREBASE_PROJECT_ID'),
        ],
    ],
];
