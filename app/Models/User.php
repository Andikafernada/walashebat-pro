<?php

namespace App\Models;

use App\Support\Phone;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_TEACHER = 'teacher';

    public const ROLE_ADMIN = 'admin';

    public const TIER_TRIAL = 'trial';

    public const TIER_PRO = 'pro';

    /** Lama masa gratis untuk setiap pendaftar baru. */
    public const BULAN_MASA_GRATIS = 3;

    protected $fillable = [
        'name',
        'nip',
        'email',
        'password',
        'whatsapp_number',
        'wa_permission_template',
        'wa_sick_template',
        'wa_magic_link_template',
        'wa_permission_keywords',
        'wa_sick_keywords',
        'school_name',
        'school_address',
        'school_city',
        'school_npsn',
        'principal_name',
        'principal_nip',
        // Sensitive fields below should NOT be fillable via mass assignment
        // 'subscription_plan',
        // 'is_active',
        // 'role',
        'wa_session_status',
        'wa_session_id',
        'wa_connected_at',
        'wa_last_error',
        // Pending registration fields
        'pending_name',
        'pending_email',
        'pending_password',
        'pending_otp',
        'pending_otp_expires_at',
        'whatsapp_verified',
        'whatsapp_otp',
        'whatsapp_otp_expires_at',
        'whatsapp_otp_attempts',
    ];

    /*
     * Tidak ada $guarded di sini, dan itu disengaja.
     *
     * Laravel mengabaikan $guarded sepenuhnya begitu $fillable terisi — hanya
     * salah satu yang berlaku. $guarded yang dulu ada di sini (berisi 'role',
     * 'is_active', 'subscription_plan') tidak melindungi apa pun; yang benar-
     * benar melindungi adalah tidak dicantumkannya kolom itu di $fillable.
     * Membiarkannya berdiri justru berbahaya: orang berikutnya bisa menambahkan
     * kolom sensitif ke $fillable sambil mengira $guarded masih menjaganya.
     *
     * Kolom sensitif — 'role', 'is_active', 'subscription_tier',
     * 'subscription_ends_at' — ditulis lewat forceFill() secara eksplisit.
     */

    /**
     * Nilai bawaan yang mencerminkan default kolom di basis data. Tanpa ini,
     * User baru tidak punya atribut is_active di memori, sehingga middleware
     * EnsureUserIsActive membacanya null dan menendang pengguna keluar.
     */
    protected $attributes = [
        'is_active' => true,
        'role' => self::ROLE_TEACHER,
        'subscription_tier' => self::TIER_TRIAL,
        'wa_session_status' => 'disconnected',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'wa_connected_at' => 'datetime',
            'pending_otp_expires_at' => 'datetime',
            'whatsapp_verified' => 'boolean',
            'whatsapp_otp_expires_at' => 'datetime',
        ];
    }

    protected function whatsappNumber(): Attribute
    {
        return Attribute::set(fn (?string $v) => Phone::normalize($v));
    }

    /** Nomor WhatsApp guru siap dipakai mengirim absensi? */
    /**
     * Catat keadaan sesi WhatsApp guru — satu-satunya pintu.
     *
     * Status sesi ditulis dari tiga tempat: saat penautan dimulai, saat
     * halaman menanyakannya berkala, dan saat gateway mendorong perubahannya
     * lewat webhook. Aturan "tersambung berarti nomornya terbukti" harus
     * berlaku di ketiganya — ditulis di satu tempat saja, dua tempat lain
     * akan diam-diam melewatkannya.
     *
     * Sesi yang tersambung untuk nomor ini membuktikan nomor itu benar milik
     * guru: WhatsApp hanya menautkan perangkat setelah pemilik nomor
     * menyetujuinya dari ponselnya sendiri. Buktinya sama kuat dengan kode
     * yang dibalas saat mendaftar, jadi tandanya ikut berubah di sini.
     *
     * Tanda itu tidak pernah dicabut saat sesi putus. Putusnya sesi berarti
     * perangkatnya tidak tertaut hari ini, bukan bahwa nomornya ternyata
     * bukan miliknya.
     */
    public function catatStatusSesi(string $status, ?string $galat = null): void
    {
        $tersambung = $status === 'connected';

        $this->forceFill([
            'wa_session_status' => $status,
            'wa_last_error' => $galat,
            'wa_connected_at' => $tersambung ? ($this->wa_connected_at ?? now()) : null,
            'whatsapp_verified' => $this->whatsapp_verified || $tersambung,
        ])->save();
    }

    public function whatsappConnected(): bool
    {
        return $this->wa_session_status === 'connected' && filled($this->whatsapp_number);
    }

    /*
    |--------------------------------------------------------------------------
    | Masa aktif otomasi WhatsApp
    |--------------------------------------------------------------------------
    | Setiap pendaftar mendapat tiga bulan gratis. Setelah habis, aplikasi tetap
    | bisa dipakai sepenuhnya — yang berhenti hanya pengiriman otomatis ke
    | WhatsApp (magic link absensi, rekap ke grup orang tua, balasan otomatis).
    |
    | Pesan transaksional TIDAK ikut berhenti: OTP reset kata sandi, kredensial
    | login siswa, dan konfirmasi pembayaran tetap terkirim. Menghentikannya
    | akan mengunci wali kelas dari akunnya sendiri dan membuat pembayaran yang
    | sudah disetujui tidak pernah sampai kabarnya.
    */

    /**
     * Satu-satunya penentu boleh-tidaknya otomasi WhatsApp berjalan.
     *
     * Sengaja dihitung dari tanggal setiap kali dipanggil, bukan dibaca dari
     * kolom status. Status tersimpan akan basi diam-diam bila proses yang
     * membaliknya mati, dan kegagalan seperti itu berpihak ke arah yang salah:
     * pengguna kedaluwarsa tetap terlayani tanpa ada yang tahu.
     */
    public function otomasiWhatsAppAktif(): bool
    {
        // Admin menjalankan platform ini; aksesnya tidak boleh bergantung pada
        // langganan, kalau tidak persetujuan pembayaran bisa ikut terkunci.
        if ($this->isAdmin()) {
            return true;
        }

        return $this->subscription_ends_at !== null
            && $this->subscription_ends_at->isFuture();
    }

    /** Sisa hari masa otomasi; 0 bila sudah habis. */
    public function sisaHariOtomasi(): int
    {
        if ($this->subscription_ends_at === null || $this->subscription_ends_at->isPast()) {
            return 0;
        }

        return (int) ceil(now()->diffInDays($this->subscription_ends_at, absolute: true));
    }

    /** Masa gratis pendaftar baru: dipakai saat registrasi dan di test. */
    public static function akhirMasaGratis(): Carbon
    {
        return now()->addMonths(self::BULAN_MASA_GRATIS);
    }

    /**
     * Tambahkan N bulan akses PRO, menumpuk di atas sisa masa yang belum
     * terpakai (tidak menghanguskannya). Satu-satunya tempat aturan penumpukan
     * ini ditulis: dipakai persetujuan pembayaran maupun pemberian PRO manual
     * oleh operator, agar keduanya tidak pernah berselisih. Mengembalikan
     * tanggal akhir yang baru.
     *
     * subscription_tier/ends_at sengaja di-guard dari mass assignment; ditulis
     * resmi lewat forceFill().
     */
    public function tambahPro(int $months): Carbon
    {
        $currentEnd = $this->subscription_ends_at && $this->subscription_ends_at->isFuture()
            ? $this->subscription_ends_at
            : now();

        $newEnd = $currentEnd->copy()->addMonths($months);

        $this->forceFill([
            'subscription_tier' => self::TIER_PRO,
            'subscription_ends_at' => $newEnd,
        ])->save();

        return $newEnd;
    }

    /** Apakah user adalah admin (kepala sekolah)? */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Apakah user adalah guru/wali kelas biasa? */
    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    /** Label role dalam Bahasa Indonesia */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Kepala Sekolah',
            self::ROLE_TEACHER => 'Wali Kelas',
            default => ucfirst($this->role),
        };
    }

    /** Notifikasi preferences */
    public function notificationPreference(): HasOne
    {
        return $this->hasOne(UserNotificationPreference::class);
    }

    /** Notifikasi user */
    public function userNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->orderByDesc('created_at');
    }

    /**
     * Semua notifikasi user.
     *
     * Menimpa relasi morphMany bawaan trait Notifiable: tabel `notifications`
     * di aplikasi ini memakai skema kustom (`user_id`, `title`, `body`, ...),
     * bukan skema polimorfik `notifiable_type`/`notifiable_id` milik Laravel.
     * Tanpa override ini relasi bawaan melempar QueryException.
     */
    public function notifications(): HasMany
    {
        return $this->userNotifications();
    }

    /** Notifikasi yang belum dibaca */
    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)
            ->whereNull('read_at')
            ->orderByDesc('created_at');
    }

    /** Notifikasi yang sudah dibaca */
    public function readNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)
            ->whereNotNull('read_at')
            ->orderByDesc('created_at');
    }

    /** Hitung notifikasi belum dibaca */
    public function unreadCount(): int
    {
        return $this->unreadNotifications()->count();
    }

    /** Buat atau update notification preference */
    public function getOrCreateNotificationPreference(): UserNotificationPreference
    {
        return $this->notificationPreference ?? $this->notificationPreference()->create([]);
    }

    /*
     * Keduanya dulu ditulis sebagai `static function ... : HasMany` yang
     * memanggil `self::hasMany()`. hasMany() adalah method instance, jadi
     * pemanggilan mana pun berakhir Error: "Non-static method ... cannot be
     * called statically" — galat fatal, bukan sekadar hasil kosong. Tidak
     * ketahuan karena memang belum ada yang memanggilnya; ranjau yang menunggu
     * pemakai pertama. Lagipula "semua guru" bukan relasi milik seorang user,
     * melainkan query pada tabel yang sama.
     */

    /** Semua guru (untuk admin view). */
    public static function teachers(): Builder
    {
        return static::query()->where('role', self::ROLE_TEACHER);
    }

    /** Semua admin. */
    public static function admins(): Builder
    {
        return static::query()->where('role', self::ROLE_ADMIN);
    }

    /** Kelas yang dimiliki (owner langsung). */
    public function classes(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    /**
     * DICADANGKAN — belum dipakai.
     * Model akses saat ini satu peran (wali kelas/owner saja). Relasi ini
     * disiapkan untuk peran guru mapel / co-wali di kemudian hari.
     */
    public function memberClasses(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'class_user', 'user_id', 'class_id')
            ->withPivot('role_in_class')
            ->withTimestamps();
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
