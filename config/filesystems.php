<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver.
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'private',
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Private Documents Disk
        |--------------------------------------------------------------------------
        |
        | SECURITY: This disk is for sensitive candidate documents that should
        | NOT be publicly accessible. All files here require authentication
        | to download via the secure-file route.
        |
        | Stored documents include:
        | - CNIC scans
        | - Passport copies
        | - Medical reports
        | - Visa documents
        | - Training certificates
        | - Remittance receipts
        | - Complaint evidence
        |
        */
        'private' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'visibility' => 'private',
            'throw' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Candidate Photos Disk
        |--------------------------------------------------------------------------
        |
        | Public disk for candidate profile photos (less sensitive)
        |
        */
        'photos' => [
            'driver' => 'local',
            'root' => storage_path('app/public/photos'),
            'url' => env('APP_URL') . '/storage/photos',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];