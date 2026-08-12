<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Phone;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Siswa juga login sendiri lewat guard `student` (config/auth.php), jadi model
 * ini WAJIB memenuhi kontrak Authenticatable — tanpa itu SessionGuard::login()
 * menolaknya dan seluruh portal siswa tidak bisa diakses.
 *
 * Yang dipasang hanya trait Authenticatable, bukan turunan dari
 * Illuminate\Foundation\Auth\User: siswa tidak memakai reset password lewat
 * email maupun gate/policy, dan tabel `students` tidak punya kolom
 * remember_token.
 */
class Student extends Model implements AuthenticatableContract
{
    use AuthenticatableTrait, BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * Catatan penamaan: kolom identitas inti memakai nama Inggris (gender,
     * address, phone, parent_phone) karena sudah dipakai lintas aplikasi.
     * Kolom profil tambahan memakai nama Indonesia. Lihat migrasi
     * 2026_07_26_000000_resolve_duplicate_student_columns.
     *
     * Kolom baru WAJIB terdaftar di sini. Sebelumnya tidak, dan akibatnya
     * fill() membuang seluruh data profil tanpa peringatan sedikit pun -- import
     * Excel melaporkan sukses sambil menulis nol baris.
     */
    protected $fillable = [
        // Identitas inti
        'user_id', 'class_id', 'nis', 'nisn', 'name', 'gender',
        'phone', 'parent_phone', 'address', 'discipline_points', 'is_active',

        // Autentikasi siswa
        'password', 'must_change_password',

        // Data pribadi
        'tempat_lahir', 'tanggal_lahir', 'agama', 'anak_ke', 'jumlah_saudara',
        'golongan_darah', 'tinggi_badan_cm', 'berat_badan_kg',

        // Domisili
        'rt_rw', 'kelurahan', 'kecamatan', 'jarak_rumah_km', 'moda_transportasi',

        // Orang tua / wali
        'nama_ayah', 'pekerjaan_ayah', 'nama_ibu', 'pekerjaan_ibu',
        'nama_wali', 'pekerjaan_wali', 'alamat_ortu',

        // Riwayat sekolah
        'asal_sekolah', 'tahun_masuk', 'kompetensi_keahlian',

        // Lain-lain
        'foto_path', 'hobi', 'cita_cita', 'penerima_kip', 'penerima_pkh',
    ];

    protected $attributes = [
        'discipline_points' => 100,
        'must_change_password' => true,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'discipline_points' => 'integer',
            'tanggal_lahir' => 'date',
            'anak_ke' => 'integer',
            'jumlah_saudara' => 'integer',
            'tinggi_badan_cm' => 'integer',
            'berat_badan_kg' => 'integer',
            'jarak_rumah_km' => 'float',
            'tahun_masuk' => 'integer',
            'penerima_kip' => 'boolean',
            'penerima_pkh' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    protected function disciplinePoints(): Attribute
    {
        return Attribute::set(fn ($value) => max(0, min(100, (int) $value)));
    }

    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $v) => Phone::normalize($v));
    }

    protected function parentPhone(): Attribute
    {
        return Attribute::set(fn (?string $v) => Phone::normalize($v));
    }

    /**
     * Warna badge untuk poin disiplin.
     *
     * Poin dimulai dari 100 dan BERKURANG setiap pelanggaran (lihat
     * ViolationController: discipline_points + nilai negatif). Jadi poin tinggi
     * adalah kabar baik. View sebelumnya memakai "poin > 0 berarti merah",
     * sehingga siswa tanpa pelanggaran tampil sebagai peringatan merah 100.
     */
    public function disciplineTone(): string
    {
        return match (true) {
            $this->discipline_points >= 80 => 'emerald',
            $this->discipline_points >= 50 => 'amber',
            default => 'rose',
        };
    }

    public function needsAttention(): bool
    {
        return $this->discipline_points < 50;
    }

    /*
     * FOTO SISWA.
     *
     * Disimpan di disk `local` (storage/app/private), bukan `public`. Ini foto
     * anak di bawah umur: di disk publik berkasnya disajikan nginx tanpa
     * melewati Laravel sama sekali, sehingga satu URL yang bocor — dari riwayat
     * peramban, tangkapan layar, atau bookmark — berlaku selamanya untuk siapa
     * pun, tanpa jejak dan tanpa cara mencabutnya. Lewat rute di bawah, hanya
     * guru pemegang kelasnya yang bisa membukanya.
     */
    public function photoUrl(): ?string
    {
        return $this->foto_path && $this->class_id
            ? route('classes.students.foto', [$this->class_id, $this])
            : null;
    }

    /**
     * Foto sebagai data URI, untuk disematkan ke PDF.
     *
     * dompdf tidak boleh disuruh mengunduh sendiri dari URL: rutenya butuh
     * sesi login yang tidak dimiliki dompdf, jadi hasilnya kotak kosong —
     * dan mengaktifkan isRemoteEnabled membuat templat mana pun bisa disuruh
     * menarik berkas dari dalam jaringan server.
     */
    public function photoDataUri(): ?string
    {
        if (! $this->foto_path || ! Storage::disk('local')->exists($this->foto_path)) {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode(Storage::disk('local')->get($this->foto_path));
    }

    /**
     * Simpan foto unggahan: dipotong jadi pas foto 3:4 lalu dimampatkan.
     *
     * Satu-satunya jalan masuk foto — dipakai guru maupun formulir biodata
     * publik. Foto dari kamera HP berukuran 3–8 MB; disimpan apa adanya,
     * 192 siswa berarti ~1 GB dan satu PDF sekelas jadi puluhan megabita yang
     * tak bisa dikirim lewat WhatsApp.
     */
    public function simpanFoto(\Illuminate\Http\UploadedFile $berkas): void
    {
        $sumber = @imagecreatefromstring(file_get_contents($berkas->getRealPath()));

        if ($sumber === false) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'foto' => 'Berkas fotonya tidak bisa dibaca. Coba unggah ulang dalam format JPG atau PNG.',
            ]);
        }

        [$lebarAsal, $tinggiAsal] = [imagesx($sumber), imagesy($sumber)];
        [$lebar, $tinggi] = [300, 400];

        // Dipotong dari tengah, bukan digepengkan: wajah yang penyok lebih
        // buruk daripada bahu yang terpotong.
        $skala = max($lebar / $lebarAsal, $tinggi / $tinggiAsal);
        $petikLebar = (int) round($lebar / $skala);
        $petikTinggi = (int) round($tinggi / $skala);

        $hasil = imagecreatetruecolor($lebar, $tinggi);
        // PNG berlatar tembus pandang jadi hitam pekat begitu ditulis ke JPEG.
        imagefill($hasil, 0, 0, imagecolorallocate($hasil, 255, 255, 255));
        imagecopyresampled(
            $hasil, $sumber,
            0, 0,
            (int) (($lebarAsal - $petikLebar) / 2), (int) (($tinggiAsal - $petikTinggi) / 2),
            $lebar, $tinggi, $petikLebar, $petikTinggi
        );

        ob_start();
        imagejpeg($hasil, null, 82);
        $jpeg = ob_get_clean();

        imagedestroy($sumber);
        imagedestroy($hasil);

        $lama = $this->foto_path;

        // Nama berkas acak, bukan NIS: nama yang bisa ditebak membuat seluruh
        // arsip foto terbuka begitu satu berkas ditemukan.
        $this->foto_path = 'siswa/foto/'.\Illuminate\Support\Str::random(40).'.jpg';
        Storage::disk('local')->put($this->foto_path, $jpeg);
        $this->save();

        if ($lama) {
            Storage::disk('local')->delete($lama);
        }
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr(trim($this->name), 0, 1));
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** Nilai siswa ini pada seluruh penilaian: harian maupun PTS/PAS. */
    public function assessmentScores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    public function seat(): HasOne
    {
        return $this->hasOne(Seat::class, 'student_id');
    }

    /**
     * Character records.
     */
    public function characterRecords(): HasMany
    {
        return $this->hasMany(CharacterRecord::class);
    }

    /**
     * Character reflections.
     */
    public function characterReflections(): HasMany
    {
        return $this->hasMany(CharacterReflection::class);
    }

    /*
     * Nama yang dicari scopeBindings() untuk parameter {record} pada rute
     * classes/{class}/karakter/{student}/catatan/{record}: induk terdekatnya
     * adalah {student}, dan Laravel memanggil records() — bukan
     * characterRecords(). Tanpa ini halaman detail catatan karakter selalu
     * berakhir galat 500.
     */
    public function records(): HasMany
    {
        return $this->characterRecords();
    }

    /**
     * Student badges.
     */
    public function studentBadges(): HasMany
    {
        return $this->hasMany(StudentBadge::class);
    }

    /**
     * Get total score for a dimension.
     */
    public function getDimensionScore(int $dimensionId, ?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $query = $this->characterRecords()->where('character_dimension_id', $dimensionId);

        if ($startDate) {
            $query->where('record_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('record_date', '<=', $endDate);
        }

        return $query->sum('score');
    }
}
