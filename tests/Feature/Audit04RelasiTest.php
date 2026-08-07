<?php

namespace Tests\Feature;

use App\Models\CharacterReflection;
use App\Models\Classroom;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Audit04RelasiTest extends TestCase
{
    use RefreshDatabase;

    private function bikinRefleksi(): array
    {
        $user = User::factory()->create();
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        $student = Student::factory()->create(['user_id' => $user->id, 'class_id' => $class->id]);

        $dimId = DB::table('character_dimensions')->insertGetId([
            'user_id' => $user->id,
            'code' => 'gotong_royong',
            'name' => 'Gotong Royong',
            'icon' => 'users',
            'color' => '#6366f1',
            'sort_order' => 1,
            'is_active' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $refId = DB::table('character_reflections')->insertGetId([
            'user_id' => $user->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'character_dimension_id' => $dimId,
            'period' => 'weekly',
            'reflection_date' => now()->toDateString(),
            'self_rating' => 4,
            'status' => 'submitted',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$user, $student, $dimId, $refId];
    }

    /**
     * Relasi dimension harus benar di KETIGA jalur pemakaiannya.
     *
     * Kolomnya bernama character_dimension_id, bukan dimension_id yang ditebak
     * Eloquent dari nama metodenya. Tebakan yang salah tidak melempar galat
     * pada pemuatan malas — ia hanya mengembalikan null, sehingga portofolio
     * karakter tampil tanpa nama dimensi seolah datanya memang belum diisi.
     * Pada whereHas akibatnya lebih keras: kueri menyebut kolom yang tidak ada
     * dan laporan meledak 500.
     */
    public function test_relasi_dimension_pada_character_reflection(): void
    {
        [$user, $student, $dimId, $refId] = $this->bikinRefleksi();
        $this->actingAs($user);

        $ref = CharacterReflection::find($refId);

        $this->assertSame(
            'character_dimension_id',
            $ref->dimension()->getForeignKeyName(),
            'relasi harus menunjuk kolom yang benar-benar ada di tabel',
        );

        // Pemuatan malas — jalur yang dipakai halaman detail.
        $this->assertSame('Gotong Royong', $ref->dimension?->name,
            'CharacterReflection::dimension() TIDAK mengembalikan dimensi yang benar');

        // Eager loading yang dipakai controller saat menampilkan daftar.
        $eager = CharacterReflection::with('dimension')->find($refId);
        $this->assertTrue($eager->relationLoaded('dimension'), 'with() harus benar-benar memuat relasinya');
        $this->assertSame($dimId, $eager->dimension?->id);
        $this->assertSame('Gotong Royong', $eager->dimension?->name);

        // whereHas seperti yang biasa dipakai laporan — dulu melempar
        // QueryException karena kolom tebakan tidak ada di tabel.
        $this->assertSame(1, CharacterReflection::whereHas('dimension')->count());

        // Dan saringannya harus benar-benar menyaring, bukan meloloskan semua.
        $this->assertSame(
            0,
            CharacterReflection::whereHas('dimension', fn ($q) => $q->where('code', 'tidak_ada'))->count(),
        );
    }

    /**
     * Tabel `notifications` di aplikasi ini berskema kustom (user_id, title,
     * body), bukan skema polimorfik bawaan Laravel. Trait Notifiable tetap
     * dipasang di User demi ->notify(), dan relasi morphMany bawaannya menyebut
     * notifiable_type/notifiable_id yang tidak ada — setiap pemanggilan
     * melempar QueryException. User menimpanya; test ini menjaga override itu
     * tidak hilang saat trait dirapikan.
     */
    public function test_relasi_notifikasi_bawaan_notifiable_pada_user(): void
    {
        $user = User::factory()->create();

        $belum = Notification::create([
            'user_id' => $user->id, 'type' => 'AttendanceReminder',
            'title' => 'Absensi belum diisi', 'body' => 'Kelas 5A belum diabsen.',
        ]);
        Notification::create([
            'user_id' => $user->id, 'type' => 'NewViolation',
            'title' => 'Pelanggaran baru', 'body' => 'Budi terlambat.',
            'read_at' => now(),
        ]);

        // Milik guru lain tidak boleh ikut terbawa.
        Notification::create([
            'user_id' => User::factory()->create()->id, 'type' => 'NewViolation',
            'title' => 'Punya orang lain', 'body' => 'Tidak boleh terlihat.',
        ]);

        foreach (['notifications' => 2, 'unreadNotifications' => 1, 'readNotifications' => 1] as $rel => $jumlah) {
            $this->assertSame($jumlah, $user->{$rel}()->count(),
                "Relasi {$rel}() rusak terhadap tabel notifications kustom");
        }

        $this->assertSame($belum->id, $user->unreadNotifications()->first()->id);
        $this->assertSame(1, $user->unreadCount());
    }
}
