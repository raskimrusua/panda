<?php

return [

    /*
    | Default disk — driven by STORAGE_BACKEND env var.
    | Application code should call `Storage::put(...)` (uses default disk),
    | NOT `Storage::disk('r2')` — never hardcode the disk name.
    | See: ~/Desktop/uwc-web-co/00-skills/app-build/laravel/skill-laravel-storage-toggle.md
    */
    'default' => env('STORAGE_BACKEND', 'r2'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Cloudflare R2 — default for Panda. S3-compatible.
        'r2' => [
            'driver' => 's3',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => env('R2_REGION', 'auto'),
            'bucket' => env('R2_BUCKET'),
            'endpoint' => env('R2_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'visibility' => 'private',
            'throw' => true,
        ],

        // AWS S3 — per-customer override (for clients with existing AWS contracts
        // or data residency requirements). Toggle via STORAGE_BACKEND=s3.
        's3' => [
            'driver' => 's3',
            'key' => env('S3_ACCESS_KEY_ID'),
            'secret' => env('S3_SECRET_ACCESS_KEY'),
            'region' => env('S3_REGION', 'us-east-1'),
            'bucket' => env('S3_BUCKET'),
            'endpoint' => env('S3_ENDPOINT'),
            'use_path_style_endpoint' => false,
            'visibility' => 'private',
            'throw' => true,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
