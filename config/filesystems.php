<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],
        /*
         * Dipakai HANYA oleh test (phpunit.xml mengalihkan FILESYSTEM_DISK ke
         * sini). Direktorinya sendiri, di luar app/private, supaya berkas uji
         * tidak pernah bertetangga dengan foto siswa dan bukti pembayaran
         * sungguhan — dan supaya menghapusnya tidak pernah berisiko.
         */
        'testing' => [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disk'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
    ],
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
