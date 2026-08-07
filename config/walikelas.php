<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    | pin_max_attempts     : maksimal percobaan PIN per sesi sebelum dikunci.
    | pin_decay_seconds    : durasi kunci PIN (detik) setelah batas tercapai.
    | magic_link_throttle  : request per menit ke endpoint magic link.
    */
    'pin_max_attempts' => (int) env('WALIKELAS_PIN_MAX_ATTEMPTS', 10),
    'pin_decay_seconds' => (int) env('WALIKELAS_PIN_DECAY_SECONDS', 600),

    /*
    | pin_hash_rounds : biaya bcrypt untuk PIN harian (BUKAN kata sandi).
    |
    | Lebih murah daripada cost 12 milik kata sandi dengan sengaja. Penjadwal
    | membuat satu sesi untuk setiap kelas yang jam pertamanya jatuh pada menit
    | yang sama; pada skala nasional itu ribuan hash dalam satu menit. Alasan
    | lengkapnya ada di AttendanceSessionService::hashPin().
    */
    'pin_hash_rounds' => (int) env('WALIKELAS_PIN_HASH_ROUNDS', 6),
    'magic_link_throttle' => (int) env('WALIKELAS_MAGIC_LINK_THROTTLE', 30),

    /*
    |--------------------------------------------------------------------------
    | Sesi Absensi (Anti-Curang)
    |--------------------------------------------------------------------------
    | pin_length          : jumlah digit PIN harian.
    | session_ttl_minutes : masa berlaku default sebuah magic link (menit).
    | token_bytes         : entropi token acak.
    */
    'pin_length' => (int) env('WALIKELAS_PIN_LENGTH', 6),
    'session_ttl_minutes' => (int) env('WALIKELAS_SESSION_TTL_MINUTES', 90),
    'token_bytes' => 32,

    /*
    |--------------------------------------------------------------------------
    | Batas Sesi Absensi per Kelas per Hari
    |--------------------------------------------------------------------------
    | Satu sesi per hari terlalu rapuh: kalau magic link telat dibuka, PIN
    | bocor, atau WhatsApp gagal terkirim, kelas itu kehilangan absensi
    | seharian tanpa jalan keluar. Batas ini memberi ruang mengulang tanpa
    | membuka pintu spam tautan.
    |
    | Sesi otomatis dari scheduler SELALU memakai urutan ke-1, jadi sesi
    | tambahan di sini adalah keputusan sadar wali kelas.
    */
    'max_sessions_per_day' => max(1, (int) env('WALIKELAS_MAX_SESSIONS_PER_DAY', 3)),

    /*
    |--------------------------------------------------------------------------
    | OTP Reset Kata Sandi (dikirim via WhatsApp)
    |--------------------------------------------------------------------------
    */
    'otp_ttl_minutes' => (int) env('WALIKELAS_OTP_TTL_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Notifikasi WhatsApp
    |--------------------------------------------------------------------------
    | driver : 'n8n' -> webhook n8n (WhatsApp Gateway workflow)
    |          'log' -> hanya tulis ke log (mode dev / tanpa gateway)
    */
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'log'),
        'n8n' => [
            'webhook_url' => env('N8N_WEBHOOK_URL'),
            'secret' => env('N8N_WEBHOOK_SECRET'),
            // Webhook terpisah untuk mengelola sesi WhatsApp tiap guru
            // (aksi: pair / status / disconnect).
            'session_url' => env('N8N_SESSION_WEBHOOK_URL'),
            'timeout' => 8,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Kehadiran
    |--------------------------------------------------------------------------
    */
    'attendance_statuses' => [
        'hadir' => 'Hadir',
        'sakit' => 'Sakit',
        'izin' => 'Izin',
        'alfa' => 'Alfa',
    ],

    /*
    |--------------------------------------------------------------------------
    | Peran Siswa dalam Struktur Organisasi Kelas
    |--------------------------------------------------------------------------
    | Peran "seksi_absensi" adalah PENERIMA magic link absensi. Pesannya
    | dikirim DARI nomor WhatsApp wali kelas.
    */
    'student_roles' => [
        'ketua' => 'Ketua Kelas',
        'wakil' => 'Wakil Ketua',
        'sekretaris' => 'Sekretaris',
        'bendahara' => 'Bendahara',
        'seksi_absensi' => 'Seksi Absensi',
        'seksi_keamanan' => 'Seksi Keamanan',
        'seksi_kebersihan' => 'Seksi Kebersihan',
        'anggota' => 'Anggota',
    ],

    /*
    |--------------------------------------------------------------------------
    | Peran Guru pada sebuah Kelas
    |--------------------------------------------------------------------------
    | DICADANGKAN — model akses saat ini satu peran (wali kelas saja).
    */
    'teacher_roles' => [
        'wali_kelas' => 'Wali Kelas',
        'guru_mapel' => 'Guru Mapel',
        'co_wali' => 'Co-Wali Kelas',
    ],
];
