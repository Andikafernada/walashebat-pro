<?php

return [
    // Tempat konfigurasi service pihak ketiga (mail, oauth, dsb).
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Web Push Notifications (VAPID keys)
    'webpush' => [
        'vapid_public_key' => env('VAPID_PUBLIC_KEY'),
        'vapid_private_key' => env('VAPID_PRIVATE_KEY'),
        'vapid_subject' => env('VAPID_SUBJECT', 'mailto:admin@walikelas.com'),
    ],
];
