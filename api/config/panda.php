<?php

return [
    'storage_backend' => env('STORAGE_BACKEND', 'r2'),
    'disease_ai_provider' => env('DISEASE_AI_PROVIDER', 'mock'),
    'crop_health_api_key' => env('CROP_HEALTH_API_KEY'),
    'crop_health_max_monthly_kes' => (int) env('CROP_HEALTH_MAX_MONTHLY_KES', 20000),
];
