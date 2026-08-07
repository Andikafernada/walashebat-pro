<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\PaymentProof;
use App\Models\Scopes\TenantScope;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Panel operator: halaman untuk pemilik aplikasi, bukan kepala sekolah.
 *
 * Dua jenis kegagalan yang ditutupi berkas ini, keduanya senyap.
 *
 * Pertama, TenantScope membatasi setiap query ke user_id yang sedang login, dan
 * akun admin tidak memiliki satu pun kelas — jadi halaman ini menampilkan nol
 * di mana-mana pada platform yang datanya lengkap. Tidak ada galat, tidak ada
 * baris log; angkanya sekadar salah, dan nol terbaca masuk akal bagi layanan
 * yang memang baru mulai.
 *
 * Kedua, isi halamannya bisa benar secara teknis tetapi menjawab pertanyaan
 * yang salah. Versi sebelumnya menyajikan rata-rata kehadiran sekolah dan saldo
 * kas per kelas — sempurna bagi kepala sekolah, jabatan yang tidak memakai
 * produk ini. Tes di bawah menuntut yang menuntut tindakan operator: verifikasi
 * pembayaran, masa aktif jatuh tempo, dan gateway yang putus.
 */
class DashboardAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => User::ROLE_ADMIN])->save();

        return $u->refresh();
    }

    /** Seorang wali kelas beserta satu kelas dan sejumlah siswa. */
    private function guruDenganKelas(string $namaGuru, string $namaKelas, int $jumlahSiswa): User
    {
        $guru = User::factory()->create(['name' => $namaGuru]);

        $kelas = Classroom::factory()->create([
            'user_id' => $guru->id,
            'name' => $namaKelas,
            'is_active' => true,
        ]);

        Student::factory()->count($jumlahSiswa)->create([
            'user_id' => $guru->id,
            'class_id' => $kelas->id,
            'is_active' => true,
        ]);

        return $guru;
    }

    // -- Inti: angkanya milik seluruh platform ------------------------------

    public function test_operator_melihat_kelas_dan_siswa_milik_guru_lain(): void
    {
        $this->guruDenganKelas('Bu Sri', 'XII IPA 1', 30);
        $this->guruDenganKelas('Pak Budi', 'XI IPS 2', 28);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            // 58 siswa, 2 kelas — bukan nol.
            ->assertSeeInOrder(['Siswa aktif', '58'])
            ->assertSeeInOrder(['Kelas aktif', '2']);
    }

    /** Akun operator sendiri bukan pelanggan dan tidak boleh ikut dihitung. */
    public function test_akun_admin_tidak_dihitung_sebagai_wali_kelas(): void
    {
        $this->guruDenganKelas('Bu Sri', 'XII IPA 1', 3);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Wali Kelas Terdaftar', '1']);
    }

    // -- Langganan: yang menuntut tindakan ----------------------------------

    public function test_bukti_pembayaran_menunggu_ditampilkan_dan_bisa_dibuka(): void
    {
        $guru = User::factory()->create(['name' => 'Bu Sri']);

        PaymentProof::create([
            'user_id' => $guru->id,
            'plan_type' => 'monthly',
            'amount' => 19000,
            'proof_image' => 'bukti/contoh.jpg',
            'sender_name' => 'SRI RAHAYU',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Bukti Menunggu Verifikasi', '1'])
            // Angkanya tidak berguna tanpa jalan menuju halaman verifikasinya.
            ->assertSee(route('admin.subscriptions.index'), false);
    }

    public function test_masa_aktif_yang_segera_habis_muncul_di_daftar_tagih(): void
    {
        User::factory()->create([
            'name' => 'Bu Sri',
            'subscription_ends_at' => now()->addDays(3),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Bu Sri')
            ->assertSee('sisa 3 hari');
    }

    public function test_masa_aktif_yang_baru_habis_muncul_sebagai_lewat_tempo(): void
    {
        User::factory()->create([
            'name' => 'Pak Budi',
            'subscription_ends_at' => now()->subDays(2),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pak Budi')
            ->assertSee('habis 2 hari lalu');
    }

    /**
     * Guru yang sudah sebulan lebih tidak memperpanjang bukan lagi tagihan yang
     * tertunda melainkan pengguna yang berhenti. Menumpuknya di daftar tindakan
     * menenggelamkan baris yang masih bisa dikerjakan hari ini.
     */
    public function test_yang_habis_lebih_dari_sebulan_tidak_menumpuk_di_daftar_tagih(): void
    {
        User::factory()->create([
            'name' => 'Pak Lama',
            'subscription_ends_at' => now()->subDays(60),
        ]);

        // Diperiksa pada datanya, bukan pada HTML: nama yang sama sah muncul di
        // daftar gateway bermasalah pada halaman yang sama, sehingga
        // assertDontSee() akan gagal karena alasan yang sama sekali berbeda.
        $perluDitagih = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->viewData('perluDitagih');

        $this->assertNotContains('Pak Lama', $perluDitagih->pluck('nama'));
    }

    /**
     * Empat segmen yang penanganannya berbeda. Yang pernah membayar lalu lewat
     * tempo ditagih ulang; yang masa gratisnya habis belum pernah membayar sama
     * sekali. Menyatukan keduanya sebagai "kedaluwarsa" menghapus perbedaan itu.
     */
    public function test_segmen_memisahkan_masa_gratis_habis_dari_berbayar_lewat_tempo(): void
    {
        User::factory()->create(['subscription_tier' => User::TIER_TRIAL, 'subscription_ends_at' => now()->subDay()]);
        User::factory()->create(['subscription_tier' => User::TIER_PRO, 'subscription_ends_at' => now()->subDay()]);
        User::factory()->create(['subscription_tier' => User::TIER_PRO, 'subscription_ends_at' => now()->addMonth()]);

        $halaman = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk();

        $halaman->assertSeeInOrder(['Masa Gratis Habis', 'calon konversi pertama']);
        $halaman->assertSeeInOrder(['Berbayar Lewat Tempo', 'pernah bayar']);

        $segmen = $halaman->viewData('segmen');
        $this->assertSame(1, $segmen['gratis_habis']);
        $this->assertSame(1, $segmen['berbayar_lewat_tempo']);
        $this->assertSame(1, $segmen['berbayar']);
    }

    // -- Kesehatan gateway & antrian ----------------------------------------

    public function test_sesi_whatsapp_bermasalah_ditampilkan_dengan_galatnya(): void
    {
        $guru = User::factory()->create(['name' => 'Bu Sri']);
        $guru->forceFill([
            'wa_session_status' => 'disconnected',
            'wa_last_error' => 'Sesi kedaluwarsa, perlu pindai ulang',
        ])->save();

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Bu Sri')
            ->assertSee('Sesi kedaluwarsa, perlu pindai ulang');
    }

    /**
     * Redis mati adalah justru saat halaman ini paling dibutuhkan: antrian tidak
     * jalan, pesan tidak terkirim, dan operator datang ke sini mencari sebabnya.
     * Kalau Queue::size() yang meledak ikut menjatuhkan halamannya, satu-satunya
     * alat diagnosis ikut hilang bersama masalahnya.
     */
    public function test_halaman_tetap_hidup_saat_redis_mati(): void
    {
        Queue::shouldReceive('size')->andThrow(new \RuntimeException('Connection refused [tcp://127.0.0.1:6379]'));

        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Redis tidak terbaca')
            ->assertSee('Connection refused [tcp://127.0.0.1:6379]');
    }

    public function test_tanggal_header_terbaca_manusia(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(now()->translatedFormat('l, d F Y'))
            // format('dddd, d MMMM Y') mencetak "06060606, 06 AugAugAugAug 2026".
            ->assertDontSee('AugAug');
    }

    // -- Batas pelepasan tenant ---------------------------------------------

    public function test_pelepasan_tenant_berakhir_saat_closure_selesai(): void
    {
        $guru = $this->guruDenganKelas('Bu Sri', 'XII IPA 1', 3);
        $admin = $this->admin();
        $this->actingAs($admin);

        $didalam = TenantScope::lintasSeluruhTenant(fn () => Classroom::count());

        $this->assertSame(1, $didalam, 'di dalam closure admin melihat kelas guru lain');
        $this->assertSame(0, Classroom::count(), 'di luar closure batasan tenant harus kembali berlaku');
        $this->assertNotNull($guru->fresh());
    }

    public function test_pelepasan_tenant_dikembalikan_walau_terjadi_galat(): void
    {
        $this->guruDenganKelas('Bu Sri', 'XII IPA 1', 3);
        $this->actingAs($this->admin());

        try {
            TenantScope::lintasSeluruhTenant(function () {
                throw new \RuntimeException('gagal di tengah jalan');
            });
        } catch (\RuntimeException) {
            // sengaja diabaikan; yang diuji adalah keadaan sesudahnya
        }

        $this->assertSame(0, Classroom::count(), 'galat tidak boleh meninggalkan scope dalam keadaan terbuka');
    }

    /** Bukan admin tidak boleh memakai jalan pintas ini, sekalipun salah panggil. */
    public function test_guru_tidak_boleh_melepas_batas_tenant(): void
    {
        $this->actingAs(User::factory()->create());

        $this->expectException(\RuntimeException::class);

        TenantScope::lintasSeluruhTenant(fn () => Classroom::count());
    }

    public function test_guru_tidak_bisa_membuka_dashboard_admin(): void
    {
        $this->guruDenganKelas('Bu Sri', 'XII IPA 1', 3);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
