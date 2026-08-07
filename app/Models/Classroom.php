<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Model kelas. Memetakan ke tabel `classes` (Class adalah reserved word PHP).
 */
class Classroom extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'classes';

    /** Kelas yang diwalikan — modul lengkap. */
    public const JENIS_PERWALIAN = 'perwalian';

    /** Kelas yang hanya diajar mapelnya — modul terbatas. */
    public const JENIS_AJAR = 'ajar';

    protected $fillable = [
        'user_id', 'name', 'jenis', 'academic_year', 'major', 'mapel',
        'homeroom_wa', 'parent_group_wa', 'auto_attendance', 'is_active',
    ];

    protected $attributes = [
        'jenis' => self::JENIS_PERWALIAN,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'auto_attendance' => 'boolean',
            'mapel' => 'array',
        ];
    }

    /**
     * Kelas ini hanya diajar mapelnya, bukan diwalikan?
     *
     * Penentu satu-satunya modul mana yang boleh tampil. Buku kas, struktur
     * organisasi, laporan administrasi, denah, dan pembagian biodata ke grup
     * orang tua adalah pekerjaan WALI KELAS — menampilkannya di kelas ajar
     * mengundang guru mengisi data yang bukan urusannya, lalu melahirkan
     * salinan kedua yang menyimpang dari milik wali kelas aslinya.
     */
    public function kelasAjar(): bool
    {
        return $this->jenis === self::JENIS_AJAR;
    }

    /**
     * Sebutan peran pemilik kelas ini: "Wali Kelas" atau "Guru Mapel".
     *
     * Pada kelas ajar, `owner` adalah guru mapelnya sendiri — wali kelasnya
     * orang lain yang tidak tercatat di sini sama sekali. Menulis "Wali Kelas:
     * <nama>" di kelas ajar karena itu bukan sekadar label yang kurang tepat,
     * melainkan pernyataan yang salah tentang siapa orang itu, dan pada lembar
     * laporan yang ditandatangani ia ikut tercetak.
     */
    public function sebutanPeran(): string
    {
        return $this->kelasAjar() ? 'Guru Mapel' : 'Wali Kelas';
    }

    /**
     * Daftar mapel yang diampu di kelas ini, sudah dibersihkan.
     *
     * @return array<int, string>
     */
    public function mapelDiampu(): array
    {
        return array_values(array_filter(
            array_map('trim', (array) ($this->mapel ?? [])),
            fn ($m) => $m !== '',
        ));
    }

    /**
     * Absensi otomatis hanya masuk akal untuk kelas perwalian.
     *
     * Magic link + PIN dikirim ke Seksi Absensi lewat grup/nomor kelas. Pada
     * kelas ajar, grup itu milik wali kelas LAIN — menyalakannya berarti
     * mengirim tautan absensi dan PIN ke percakapan yang bukan milik pengirim.
     * Karena itu penjagaannya di model, bukan hanya di formulir: jalur mana pun
     * yang menyalakannya tetap tertutup.
     */
    public function bolehAbsensiOtomatis(): bool
    {
        return ! $this->kelasAjar();
    }

    /**
     * Menghapus kelas ikut mengarsipkan siswanya, dan memulihkan kelas
     * memulihkan mereka kembali. Tanpa ini, siswa dari kelas terarsip masih
     * terhitung pada statistik dashboard.
     */
    protected function homeroomWa(): Attribute
    {
        return Attribute::set(fn (?string $v) => Phone::normalize($v));
    }

    protected static function booted(): void
    {
        /*
         * Setiap kelas lahir dengan token tautan publiknya sendiri.
         *
         * Dibuat di sini, bukan di controller, karena kelas dibuat dari
         * beberapa tempat (formulir wali kelas, seeder, factory pada test).
         * Kelas tanpa token akan membuat route('public.biodata.show', $kelas)
         * melempar UrlGenerationException — tombol "Salin Tautan" di halaman
         * siswa akan menjatuhkan seluruh halaman, bukan sekadar gagal menyalin.
         */
        static::creating(function (self $classroom) {
            if (blank($classroom->public_token)) {
                $classroom->public_token = static::buatTokenPublik();
            }
        });

        /*
         * Penjagaan terakhir absensi otomatis pada kelas ajar.
         *
         * Diletakkan di sini, bukan hanya di validasi formulir, karena
         * akibatnya berada di luar aplikasi: magic link dan PIN terkirim ke
         * grup WhatsApp orang tua milik wali kelas lain. Satu jalur yang lupa
         * memeriksa sudah cukup untuk itu terjadi.
         */
        static::saving(function (self $classroom) {
            if ($classroom->auto_attendance && ! $classroom->bolehAbsensiOtomatis()) {
                $classroom->auto_attendance = false;
            }
        });

        static::deleted(function (self $classroom) {
            if (! $classroom->isForceDeleting()) {
                $classroom->students()->delete();
            }
        });

        static::restored(function (self $classroom) {
            $classroom->students()->onlyTrashed()->restore();
        });
    }

    /**
     * Token untuk tautan formulir mandiri (biodata & refleksi).
     *
     * Sengaja TIDAK masuk $fillable: kepemilikan tautan tidak boleh bisa
     * dititipkan lewat request. Panjang 32 karakter alfanumerik Str::random()
     * memakai sumber acak kriptografis, jadi menebaknya tidak masuk akal.
     */
    public static function buatTokenPublik(): string
    {
        return Str::random(32);
    }

    /**
     * Token yang dijamin ada.
     *
     * Kelas yang sudah tersimpan sebelum kolom ini ada — atau tersisip lewat
     * jalur yang melewati event creating, misalnya insert massal — akan
     * memiliki token kosong. Membiarkannya berarti tautan publik kelas itu
     * tidak bisa dibuat sama sekali; menambalnya di sini membuat kekurangan itu
     * sembuh sendiri saat pertama kali tautannya dibutuhkan.
     */
    public function tokenPublik(): string
    {
        if (blank($this->public_token)) {
            $this->forceFill(['public_token' => static::buatTokenPublik()])->save();
        }

        return $this->public_token;
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /** Penilaian harian (nilai per Capaian Pembelajaran). */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'class_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    public function organizationStructures(): HasMany
    {
        return $this->hasMany(OrganizationStructure::class, 'class_id');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class, 'class_id');
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class, 'class_id');
    }

    public function cashBooks(): HasMany
    {
        return $this->hasMany(CashBook::class, 'class_id');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'class_id');
    }

    /*
     * Dua relasi di bawah dipakai scopeBindings() pada rute portofolio
     * karakter: {record} dan {reflection} dicarikan lewat records() dan
     * reflections() milik {class}. Tanpa keduanya setiap pembukaan detail
     * catatan, penyuntingan, penghapusan, konfirmasi, maupun pemberian umpan
     * balik berakhir "Call to undefined method" — galat 500 tanpa kecuali.
     * Selama ini tidak ketahuan karena daftar dimensi karakter kosong,
     * sehingga tidak ada satu pun catatan yang bisa dibuat lalu dibuka.
     */
    public function records(): HasMany
    {
        return $this->hasMany(CharacterRecord::class, 'class_id');
    }

    public function reflections(): HasMany
    {
        return $this->hasMany(CharacterReflection::class, 'class_id');
    }

    /**
     * DICADANGKAN — belum dipakai.
     * Lihat catatan pada ClassroomPolicy: akses saat ini hanya wali kelas.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_user', 'class_id', 'user_id')
            ->withPivot('role_in_class')
            ->withTimestamps();
    }

    /** Siswa yang menjabat sebagai Seksi Absensi — penerima magic link absensi. */
    public function attendanceOfficer()
    {
        return $this->organizationStructures()
            ->where('role', 'seksi_absensi')
            ->with('student')
            ->first()?->student;
    }
}
