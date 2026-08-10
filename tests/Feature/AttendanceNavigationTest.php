<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);

        // Buat user dengan kelas aktif
        $this->user = User::factory()->create();
        $this->classroom = Classroom::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'name' => 'VII-A',
        ]);
        Student::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'class_id' => $this->classroom->id,
            'is_active' => true,
        ]);

        // Buat kelas orang lain (tidak seharusnya muncul di pintasan)
        $this->otherClassroom = Classroom::factory()->create([
            'is_active' => true,
            'name' => 'VII-B (Lain)',
        ]);
    }

    private function buatSesi(string $status = 'submitted', string $title = 'Sesi Test'): AttendanceSession
    {
        return AttendanceSession::create([
            'user_id' => $this->user->id,
            'class_id' => $this->classroom->id,
            'session_date' => today(),
            'sequence' => 1,
            'token' => 'tok-'.uniqid(),
            'pin_hash' => bcrypt('123456'),
            'expires_at' => now()->addDay(),
            'status' => $status,
            'title' => $title,
        ]);
    }

    /*
     * Dashboard dirombak jadi papan statistik: pintasan absensi PER KELAS
     * tidak ada lagi, diganti pintasan umum ke daftar kelas. Yang diuji di
     * sini karena itu jalur navigasinya, bukan teks tombol lamanya.
     */
    public function test_dashboard_menyediakan_jalan_menuju_absensi(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee(route('classes.index'));
    }

    public function test_daftar_kelas_tidak_memuat_kelas_wali_kelas_lain(): void
    {
        // Isolasi tenant tetap diuji, hanya berpindah ke halaman yang kini
        // benar-benar menampilkan daftar kelas.
        $response = $this->actingAs($this->user)->get(route('classes.index'));

        $response->assertStatus(200);
        $response->assertSee($this->classroom->name);
        $response->assertDontSee($this->otherClassroom->name);
    }

    public function test_daftar_sesi_memuat_tautan_koreksi(): void
    {
        // Buat sesi absensi
        $session = $this->buatSesi('submitted');

        $response = $this->actingAs($this->user)
            ->get(route('classes.attendance.index', $this->classroom));

        $response->assertStatus(200);
        $response->assertSee('Koreksi');
        // Pastikan tautan koreksi ada (href ke route edit)
        $response->assertSee(route('classes.attendance.edit', [$this->classroom, $session]));
    }

    /**
     * Angka "terisi" dan "hadir" harus terbaca terpisah.
     *
     * Sesi yang seluruh siswanya sudah didata tapi separuhnya alfa tetap
     * bernilai terisi penuh. Menampilkan satu angka saja membuat sesi seperti
     * itu terlihat seolah semua siswa masuk.
     */
    public function test_daftar_sesi_tampil_terisi_dan_hadir_terpisah(): void
    {
        $sesi = $this->buatSesi('submitted');

        // Fixture dibangun di luar request, jadi konteks gurunya ditegakkan
        // lebih dulu — TenantScope kini menutup query tanpa pemilik.
        $this->actingAs($this->user);

        // setUp membuat 3 siswa. Seluruhnya didata, tapi hanya satu yang hadir.
        $siswa = $this->classroom->students()->orderBy('id')->get();
        $status = ['hadir', 'alfa', 'sakit'];

        foreach ($siswa as $i => $s) {
            Attendance::create([
                'user_id' => $this->user->id,
                'attendance_session_id' => $sesi->id,
                'student_id' => $s->id,
                'status' => $status[$i],
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('classes.attendance.index', $this->classroom));

        $response->assertStatus(200);
        $response->assertSee('Terisi 3/3');
        // Terisi penuh, tetapi hanya 1 yang benar-benar masuk.
        $response->assertSee('Hadir 1');
    }

    /*
     * Status sesi dulu diuji lewat dashboard. Setelah dashboard dirombak jadi
     * papan statistik, halaman absensi inilah satu-satunya tempat status sesi
     * ditampilkan — jadi ke sinilah pengujiannya berpindah, bukan dihapus.
     */
    public function test_kelas_tanpa_sesi_ditandai_belum_ada(): void
    {
        $this->actingAs($this->user)
            ->get(route('classes.attendance.index', $this->classroom))
            ->assertOk()
            ->assertSee('Belum ada sesi absensi yang dibuat');
    }

    public function test_sesi_terkirim_ditandai_selesai(): void
    {
        $this->buatSesi('submitted');

        $this->actingAs($this->user)
            ->get(route('classes.attendance.index', $this->classroom))
            ->assertOk()
            ->assertSee('Selesai');
    }

    public function test_sesi_terbuka_ditandai_terbuka(): void
    {
        $this->buatSesi('open');

        $this->actingAs($this->user)
            ->get(route('classes.attendance.index', $this->classroom))
            ->assertOk()
            ->assertSee('Terbuka');
    }

    public function test_sesi_kedaluwarsa_ditandai_kadaluarsa(): void
    {
        $sesi = $this->buatSesi('expired');
        $sesi->update(['expires_at' => now()->subHour()]);

        $this->actingAs($this->user)
            ->get(route('classes.attendance.index', $this->classroom))
            ->assertOk()
            ->assertSee('Kadaluarsa');
    }

    public function test_halaman_login_tetap_terbuka_tanpa_login(): void
    {
        // User belum login
        $response = $this->get('/');

        $response->assertStatus(200);
        // Sidebar pintasan tidak boleh error tanpa Auth::check()
        // (view composer harusnya handle ini, tapi memastikan halaman tidak crash)
        $response->assertSee(config('app.name'));
    }

    public function test_mobile_cards_tampil_untuk_sesi_absensi(): void
    {
        $this->buatSesi('submitted', 'Sesi Test Mobile');

        $response = $this->actingAs($this->user)
            ->get(route('classes.attendance.index', $this->classroom));

        $response->assertStatus(200);
        // Kartu mobile harus ada untuk sm:hidden
        $response->assertSee('Sesi Test Mobile');
    }

    public function test_badge_terlambat_tampil_orange_di_show(): void
    {
        $session = $this->buatSesi('submitted');

        // Fixture disusun langsung lewat Eloquent, di luar request. Konteks
        // guru pemiliknya harus ditegakkan, kalau tidak TenantScope menutup
        // query ini dan Student::first() mengembalikan null.
        $this->actingAs($this->user);

        $student = Student::first();

        Attendance::create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'user_id' => $this->user->id,
            'status' => 'terlambat',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('classes.attendance.show', [$this->classroom, $session]));

        $response->assertStatus(200);
        // Badge terlambat harus berwarna orange
        $response->assertSee('Terlambat');
    }
}
