<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * TenantScope harus gagal TERTUTUP.
 *
 * Audit 2026-08-10 menemukan kebalikannya: begitu tidak ada yang login, scope
 * melepas filternya sama sekali, dan setiap exception ditelan dengan hasil yang
 * sama. Dibuktikan langsung di produksi saat itu — sebagai tamu,
 * Classroom::count() mengembalikan SELURUH 9 kelas dari 3 tenant, dan
 * Student::count() seluruh 191 siswa.
 *
 * Yang menyelamatkan selama ini hanyalah setiap controller kebetulan menyaring
 * sendiri. Satu yang lupa sudah cukup, dan gagalnya senyap: bukan galat,
 * melainkan data sekolah lain yang muncul begitu saja.
 */
class TenantScopeGagalTertutupTest extends TestCase
{
    use RefreshDatabase;

    private User $buRina;

    private User $pakBudi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buRina = User::factory()->create();
        $this->pakBudi = User::factory()->create();

        // Nama ditetapkan tegas: factory bisa menghasilkan nama yang sama untuk
        // keduanya, dan assertDontSee() di bawah akan gagal karena tabrakan
        // nama, bukan karena datanya bocor.
        $nama = ['Kelas Milik Bu Rina', 'Kelas Milik Pak Budi'];

        foreach ([$this->buRina, $this->pakBudi] as $i => $guru) {
            $kelas = Classroom::factory()->create([
                'user_id' => $guru->id,
                'name' => $nama[$i],
            ]);
            Student::factory()->create([
                'user_id' => $guru->id,
                'class_id' => $kelas->id,
            ]);
        }
    }

    public function test_tamu_tidak_melihat_apa_pun(): void
    {
        $this->assertFalse(Auth::check());

        $this->assertSame(0, Classroom::count(),
            'Tanpa tenant yang bisa ditentukan, scope harus menutup — bukan melepas filter');
        $this->assertSame(0, Student::count());
    }

    public function test_guru_hanya_melihat_miliknya(): void
    {
        $this->actingAs($this->buRina);

        $this->assertSame(1, Classroom::count());
        $this->assertSame($this->buRina->id, Classroom::first()->user_id);
    }

    public function test_siswa_yang_login_hanya_melihat_tenant_gurunya(): void
    {
        /*
         * Lubang paling tajam dari temuan itu. Portal siswa memakai guard
         * 'student', sedangkan Auth::check() hanya memeriksa guard bawaan
         * ('web') — siswa yang sudah login karenanya terbaca sebagai tamu, dan
         * dulu itu berarti seluruh tenant terbuka baginya.
         */
        $siswa = Student::withoutTenant()->where('user_id', $this->buRina->id)->firstOrFail();

        Auth::guard('student')->login($siswa);

        $this->assertFalse(Auth::check(), 'Guard bawaan memang tetap kosong — itulah sebabnya dulu bocor');

        $this->assertSame(1, Classroom::count());
        $this->assertSame($this->buRina->id, Classroom::first()->user_id,
            'Tenant siswa adalah pemilik datanya, bukan siswa itu sendiri');
    }

    public function test_withoutTenant_tetap_menjadi_jalan_keluar_yang_tegas(): void
    {
        // Jalur publik yang sah (login siswa, magic link) bergantung padanya.
        $this->assertSame(2, Classroom::withoutTenant()->count());
    }

    public function test_token_publik_menetapkan_tenant_untuk_sisa_request(): void
    {
        /*
         * Halaman biodata dibuka tanpa login, tetapi tenantnya TIDAK benar-benar
         * tidak diketahui: pemegang token sudah membuktikan haknya atas satu
         * kelas. Tanpa penetapan itu, halaman ini akan kosong tanpa galat.
         */
        $kelas = Classroom::withoutTenant()->where('user_id', $this->buRina->id)->firstOrFail();

        $this->get(route('public.biodata.show', $kelas->public_token))
            ->assertOk()
            ->assertSee($kelas->name);
    }

    public function test_token_publik_tidak_membuka_kelas_lain(): void
    {
        $kelas = Classroom::withoutTenant()->where('user_id', $this->buRina->id)->firstOrFail();
        $kelasLain = Classroom::withoutTenant()->where('user_id', $this->pakBudi->id)->firstOrFail();

        $this->get(route('public.biodata.show', $kelas->public_token))
            ->assertOk()
            ->assertDontSee($kelasLain->name);
    }

    public function test_token_palsu_ditolak(): void
    {
        $this->get(route('public.biodata.show', 'token-karangan-yang-tidak-ada'))
            ->assertNotFound();
    }
}
