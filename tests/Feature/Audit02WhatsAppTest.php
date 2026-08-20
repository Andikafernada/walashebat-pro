<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\User;
use App\Services\AttendanceSessionService;
use App\Support\CircuitBreaker;
use App\Support\Contracts\NotificationChannel;
use App\Support\Notifications\N8nWhatsAppChannel;
use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Jalur WhatsApp: normalisasi nomor, webhook masuk, dan circuit breaker.
 *
 * Semula tiga belas probe yang mencetak keadaan ke STDERR lalu ditutup
 * assertTrue(true) — ikut gagal saat kode berubah, tanpa menjaga apa pun.
 *
 * Yang dikunci di sini hanya perilaku yang memang BENAR. Beberapa hal yang
 * ditemukan probe ini justru cacat, dan sengaja TIDAK dijadikan assertion:
 * menuliskannya sebagai "yang diharapkan" akan mengubah bug menjadi kontrak
 * resmi, sehingga siapa pun yang nanti memperbaikinya malah dianggap merusak
 * test. Daftarnya ada di komentar masing-masing.
 */
class Audit02WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    // -- Normalisasi nomor --------------------------------------------------

    /**
     * Bentuk-bentuk yang benar-benar ditemui di lapangan harus mendarat di
     * format 62 tanpa spasi, tanda hubung, atau tanda kurung.
     *
     * CATATAN CACAT (tidak dikunci di sini): masukan sampah menghasilkan nomor
     * yang tampak sah — '0' menjadi '62', '08' menjadi '628', dan
     * '<script>0812345</script>' menjadi '62812345'. Tidak ada pemeriksaan
     * panjang: 22 digit pun diterima. Nomor semacam itu diteruskan ke gateway
     * seolah nomor sungguhan.
     */
    public function test_nomor_lazim_dinormalkan_ke_format_62(): void
    {
        $harapan = [
            '081234567890' => '6281234567890',
            '+62 812-3456-7890' => '6281234567890',
            '62 812 3456 7890' => '6281234567890',
            '0812 3456 7890 ' => '6281234567890',
            '0812-3456-7890 (rumah)' => '6281234567890',
        ];

        foreach ($harapan as $masuk => $keluar) {
            $this->assertSame($keluar, Phone::normalize($masuk), "gagal menormalkan: {$masuk}");
        }
    }

    public function test_masukan_kosong_atau_bukan_angka_menghasilkan_null(): void
    {
        foreach (['', '  ', 'abcdefg'] as $masuk) {
            $this->assertNull(Phone::normalize($masuk), 'seharusnya null: '.var_export($masuk, true));
        }
    }

    // -- Kegagalan gateway tercatat pada sesinya ----------------------------

    /**
     * Gateway yang menolak harus meninggalkan jejak di sesinya sendiri.
     *
     * Tanpa itu wali kelas melihat sesi yang tampak baik-baik saja sementara
     * tautannya tidak pernah sampai ke siapa pun, dan tidak ada tempat untuk
     * mencari tahu sebabnya.
     */
    public function test_penolakan_gateway_tercatat_pada_sesi(): void
    {
        config(['walikelas.whatsapp.driver' => 'log']);

        $user = User::factory()->create(['whatsapp_number' => '6281111111111']);
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $this->app->instance(NotificationChannel::class, new class implements NotificationChannel
        {
            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                return false;
            }
        });

        $svc = app(AttendanceSessionService::class);
        ['session' => $session, 'pin' => $pin] = $svc->create(classroom: $class);

        /*
         * Di test QUEUE_CONNECTION=sync, sehingga job berjalan di dalam
         * permintaan ini dan kegagalannya melempar ke pemanggil. Di produksi
         * antreannya redis: kegagalan yang sama terjadi di worker. Karena itu
         * yang dikunci adalah JEJAKNYA di basis data, bukan exception-nya —
         * jejak itu sama di kedua lingkungan.
         */
        try {
            $svc->dispatchMagicLink($session, '6282222222222', $pin, $user->whatsapp_number);
        } catch (\Throwable) {
            // sengaja ditelan; yang diuji keadaan sesudahnya
        }

        $session->refresh();

        $this->assertSame('failed', $session->delivery_status);
        $this->assertNotNull($session->delivery_error);
        $this->assertNull($session->delivered_at, 'yang gagal tidak boleh bertanda sudah terkirim');
    }

    /** Sesi tetap lahir walau pengirimannya gagal — PIN-nya masih bisa dibacakan. */
    public function test_sesi_tetap_ada_walau_pengiriman_ditolak(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        config(['walikelas.whatsapp.driver' => 'log']);

        $user = User::factory()->create(['whatsapp_number' => '6281111111111']);
        $class = Classroom::factory()->create(['user_id' => $user->id, 'homeroom_wa' => '6282222222222']);
        $this->actingAs($user);

        $this->app->instance(NotificationChannel::class, new class implements NotificationChannel
        {
            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                return false;
            }
        });

        try {
            $this->post(route('classes.attendance.store', $class), [
                'target_number' => '6282222222222',
                'send_wa' => '1',
            ]);
        } catch (\Throwable) {
            // lihat catatan queue sync di test sebelumnya
        }

        $sesi = AttendanceSession::withoutTenant()->latest('id')->first();

        $this->assertNotNull($sesi, 'sesi harus tetap tercipta');
        $this->assertSame('failed', $sesi->delivery_status);
    }

    // -- Webhook masuk ------------------------------------------------------

    public function test_webhook_menolak_tanpa_secret_dan_secret_salah(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => 'rahasia-benar']);
        $user = User::factory()->create(['wa_session_id' => 'guru-99', 'wa_session_status' => 'disconnected']);

        $payload = ['session_id' => 'guru-99', 'status' => 'connected'];

        $this->postJson('/api/webhooks/whatsapp-session', $payload)->assertForbidden();
        $this->withHeader('X-Webhook-Secret', 'salah')
            ->postJson('/api/webhooks/whatsapp-session', $payload)->assertForbidden();

        $this->assertSame('disconnected', $user->refresh()->wa_session_status,
            'permintaan yang ditolak tidak boleh sempat mengubah apa pun');
    }

    public function test_webhook_dengan_secret_benar_memperbarui_sesi(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => 'rahasia-benar']);
        $user = User::factory()->create(['wa_session_id' => 'guru-99', 'wa_session_status' => 'disconnected']);

        $this->withHeader('X-Webhook-Secret', 'rahasia-benar')
            ->postJson('/api/webhooks/whatsapp-session', ['session_id' => 'guru-99', 'status' => 'connected'])
            ->assertOk();

        $user->refresh();
        $this->assertSame('connected', $user->wa_session_status);
        $this->assertNotNull($user->wa_connected_at);
    }

    /** Gateway mengirim ulang saat ragu; kiriman kembar tidak boleh jadi masalah. */
    public function test_webhook_tahan_kiriman_berulang(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => 'rahasia-benar']);
        $user = User::factory()->create(['wa_session_id' => 'guru-99', 'wa_session_status' => 'disconnected']);
        $payload = ['session_id' => 'guru-99', 'status' => 'connected'];

        foreach (range(1, 4) as $ke) {
            $this->withHeader('X-Webhook-Secret', 'rahasia-benar')
                ->postJson('/api/webhooks/whatsapp-session', $payload)
                ->assertOk();
        }

        $this->assertSame('connected', $user->refresh()->wa_session_status);
    }

    public function test_webhook_menolak_sesi_tak_dikenal_dan_payload_cacat(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => 'rahasia-benar']);
        User::factory()->create(['wa_session_id' => 'guru-99']);

        // Sesi yang tidak ada — termasuk yang berisi upaya injeksi SQL.
        $this->withHeader('X-Webhook-Secret', 'rahasia-benar')
            ->postJson('/api/webhooks/whatsapp-session', [
                'session_id' => "guru-99' OR 1=1 --",
                'status' => 'connected',
            ])
            ->assertNotFound()
            ->assertJson(['ok' => false, 'reason' => 'unknown_session']);

        // Tipe yang salah ditolak validasi, bukan diteruskan ke query.
        $this->withHeader('X-Webhook-Secret', 'rahasia-benar')
            ->postJson('/api/webhooks/whatsapp-session', [
                'session_id' => ['a' => 'b'],
                'status' => 'connected',
            ])
            ->assertStatus(422);
    }

    /**
     * Secret yang tidak dikonfigurasi harus GAGAL TERTUTUP.
     *
     * Kalau config kosong diperlakukan sebagai "tidak perlu secret", satu
     * kesalahan penyetelan di server baru membuka webhook untuk siapa saja —
     * dan tepat pada saat itu tidak ada yang tampak rusak.
     */
    public function test_webhook_tertutup_saat_secret_belum_disetel(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => null]);
        User::factory()->create(['wa_session_id' => 'guru-1']);

        $payload = ['session_id' => 'guru-1', 'status' => 'connected'];

        $this->postJson('/api/webhooks/whatsapp-session', $payload)->assertForbidden();
        $this->withHeader('X-Webhook-Secret', '')
            ->postJson('/api/webhooks/whatsapp-session', $payload)->assertForbidden();
    }

    /** Webhook hanya menyentuh sesi yang disebut, bukan guru lain. */
    public function test_webhook_hanya_mengubah_sesi_yang_disebut(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => 'rahasia']);

        $sasaran = User::factory()->create(['wa_session_status' => 'connected', 'wa_connected_at' => now()]);
        $sasaran->update(['wa_session_id' => 'guru-'.$sasaran->id]);

        $lain = User::factory()->create(['wa_session_status' => 'connected', 'wa_connected_at' => now()]);
        $lain->update(['wa_session_id' => 'guru-'.$lain->id]);

        $this->withHeader('X-Webhook-Secret', 'rahasia')
            ->postJson('/api/webhooks/whatsapp-session', [
                'session_id' => 'guru-'.$sasaran->id,
                'status' => 'disconnected',
                'error' => 'diputus dari gateway',
            ])->assertOk();

        $this->assertSame('disconnected', $sasaran->refresh()->wa_session_status);
        $this->assertSame('connected', $lain->refresh()->wa_session_status, 'guru lain tidak boleh ikut terputus');
    }

    // -- Circuit breaker ----------------------------------------------------

    /**
     * Keadaannya harus bertahan lewat cache, bukan hanya di dalam satu objek.
     *
     * Tiap permintaan HTTP membuat instance baru; kalau hitungannya ikut lahir
     * baru, circuit tidak akan pernah terbuka di produksi berapa pun seringnya
     * gateway gagal.
     *
     * Dan begitu masa tunggu lewat, half_open hanya boleh meloloskan SATU
     * pengintai. Bila seluruh antrean menyerbu bersamaan, gateway yang baru
     * pulih langsung jatuh lagi.
     */
    public function test_circuit_terbuka_setelah_ambang_dan_bertahan_lintas_instance(): void
    {
        $buatCb = fn () => new CircuitBreaker('audit-cb', failureThreshold: 3, resetTimeout: 60, successThreshold: 2);

        $cb = $buatCb();

        $this->assertSame('closed', $cb->getStatus()['state']);

        $cb->recordFailure();
        $cb->recordFailure();
        $this->assertTrue($cb->isAvailable(), 'di bawah ambang masih boleh lewat');
        $this->assertSame('closed', $cb->getStatus()['state']);

        $cb->recordFailure();
        $this->assertSame('open', $cb->getStatus()['state']);
        $this->assertFalse($cb->isAvailable());

        // Instance baru harus melihat keadaan yang sama.
        $cbLain = $buatCb();
        $this->assertSame('open', $cbLain->getStatus()['state']);
        $this->assertFalse($cbLain->isAvailable());

        // Masa tunggu lewat, lalu sepuluh worker antrian berebut di detik yang
        // sama — masing-masing instance sendiri, persis seperti di produksi.
        cache()->put('circuit_breaker:audit-cb:opened_at', time() - 61, 600);

        $pengintai = null;
        $lolos = 0;
        foreach (range(1, 10) as $ke) {
            $worker = $buatCb();
            if ($worker->isAvailable()) {
                $lolos++;
                $pengintai = $worker;
            }
        }

        $this->assertSame(1, $lolos, 'hanya satu pengintai boleh menyentuh gateway yang baru pulih');
        $this->assertSame('half_open', $cb->getStatus()['state']);

        // Selama pengintai belum melapor, tidak ada yang boleh menyusul.
        $this->assertFalse($buatCb()->isAvailable(), 'giliran kedua harus menunggu laporan pengintai pertama');

        // Sukses pertama belum menutup circuit, tapi izinnya wajib dilepas —
        // kalau tidak, pemulihan tertahan sampai izin itu kedaluwarsa sendiri.
        $pengintai->recordSuccess();
        $this->assertSame('half_open', $cb->getStatus()['state'], 'satu sukses belum cukup');
        $this->assertTrue($buatCb()->isAvailable(), 'setelah pengintai melapor, giliran berikutnya jalan');
    }

    public function test_circuit_pulih_lewat_half_open_setelah_masa_tunggu(): void
    {
        $cb = new CircuitBreaker('audit-cb2', failureThreshold: 3, resetTimeout: 60, successThreshold: 2);
        $cb->recordFailure();
        $cb->recordFailure();
        $cb->recordFailure();
        $this->assertFalse($cb->isAvailable());

        // Majukan waktu dengan menggeser penanda pembukaan ke masa lalu.
        cache()->put('circuit_breaker:audit-cb2:opened_at', time() - 61, 600);

        $this->assertTrue($cb->isAvailable(), 'setelah masa tunggu harus boleh mencoba lagi');
        $this->assertSame('half_open', $cb->getStatus()['state']);

        $cb->recordSuccess();
        $this->assertSame('half_open', $cb->getStatus()['state'], 'satu sukses belum cukup');

        $cb->recordSuccess();
        $this->assertSame('closed', $cb->getStatus()['state'], 'dua sukses menutup circuit');
        $this->assertSame(0, $cb->getStatus()['failures']);
    }

    /**
     * Keadaan yang hilang sebagian tidak boleh berujung macet permanen.
     *
     * Bila kunci 'opened_at' lenyap sementara 'state' masih open — cache
     * ditendang, TTL tak sinkron — circuit harus memperlakukannya sebagai masa
     * tunggu yang sudah lewat dan mengirim satu pengintai. Kalau tidak, gateway
     * yang sudah sehat tetap dianggap mati sampai ada yang membersihkan cache
     * dengan tangan: pemadaman yang butuh turun tangan manual.
     */
    public function test_circuit_open_tanpa_penanda_waktu_pulih_sendiri(): void
    {
        $cb = new CircuitBreaker('audit-macet', failureThreshold: 3, resetTimeout: 60);
        $cb->trip();

        cache()->forget('circuit_breaker:audit-macet:opened_at');

        $this->assertSame('open', $cb->getStatus()['state']);
        $this->assertTrue($cb->isAvailable(), 'penanda waktu hilang tidak boleh mengunci circuit selamanya');
        $this->assertSame('half_open', $cb->getStatus()['state']);

        // Tetap satu pengintai saja — pemulihan darurat bukan alasan menyerbu.
        $this->assertFalse((new CircuitBreaker('audit-macet', failureThreshold: 3, resetTimeout: 60))->isAvailable());
    }

    /** Kehilangan penanda waktu di tengah half_open pun tidak boleh mengunci. */
    public function test_penanda_waktu_hilang_saat_half_open_tidak_mengunci_circuit(): void
    {
        $cb = new CircuitBreaker('audit-macet2', failureThreshold: 3, resetTimeout: 60);
        $cb->trip();
        cache()->put('circuit_breaker:audit-macet2:opened_at', time() - 61, 600);

        $this->assertTrue($cb->isAvailable());
        $cb->recordFailure(); // pengintai gagal → open lagi, penanda waktu baru
        $this->assertSame('open', $cb->getStatus()['state']);

        cache()->forget('circuit_breaker:audit-macet2:opened_at');
        $this->assertTrue(
            (new CircuitBreaker('audit-macet2', failureThreshold: 3, resetTimeout: 60))->isAvailable(),
            'instance lain pun harus bisa mengintai ulang tanpa campur tangan manual',
        );
    }

    /**
     * `walikelas:circuit-reset` harus benar-benar mengembalikan keadaan bersih.
     *
     * Perintah itu dulu menyusun sendiri daftar kunci cache mentah, sehingga
     * selalu ketinggalan satu langkah dari kelasnya: kunci izin pengintai yang
     * ditambahkan belakangan tidak ikut terhapus. Operator menjalankan reset,
     * melihat "berhasil", padahal izin yang tertinggal masih menahan pengintai
     * berikutnya — persis saat gateway sedang ditunggu pulih.
     */
    public function test_perintah_reset_membersihkan_seluruh_kunci_termasuk_izin_pengintai(): void
    {
        $cb = new CircuitBreaker('whatsapp-gateway', failureThreshold: 3, resetTimeout: 60);
        $cb->trip();
        cache()->put('circuit_breaker:whatsapp-gateway:opened_at', time() - 61, 600);
        $this->assertTrue($cb->isAvailable(), 'ambil izin pengintai lebih dulu');
        $this->assertNotNull(cache()->get('circuit_breaker:whatsapp-gateway:half_open_probe'));

        $this->artisan('walikelas:circuit-reset')
            ->expectsConfirmation('Reset semua circuit breaker?', 'yes')
            ->assertSuccessful();

        // Izin pengintai dan penanda waktu harus benar-benar lenyap; keduanya
        // bermakna hanya selama circuit terbuka.
        foreach (['opened_at', 'half_open_probe'] as $sisa) {
            $this->assertNull(
                cache()->get("circuit_breaker:whatsapp-gateway:{$sisa}"),
                "kunci {$sisa} tertinggal setelah reset",
            );
        }

        // `state` dan hitungannya sengaja DITULIS ke nilai bersih, bukan
        // dihapus — keadaan tertutup yang tegas lebih baik daripada mengandalkan
        // nilai bawaan saat kuncinya kebetulan tidak ada.
        $status = (new CircuitBreaker('whatsapp-gateway'))->getStatus();
        $this->assertSame('closed', $status['state']);
        $this->assertSame(0, $status['failures']);

        // Dan yang paling penting: pengintai berikutnya tidak tertahan sisa izin.
        $lagi = new CircuitBreaker('whatsapp-gateway', failureThreshold: 3, resetTimeout: 60);
        $lagi->trip();
        cache()->put('circuit_breaker:whatsapp-gateway:opened_at', time() - 61, 600);
        $this->assertTrue($lagi->isAvailable(), 'izin yang tertinggal akan menahan pengintai berikutnya');
    }

    // -- Channel n8n --------------------------------------------------------

    /**
     * Inti gunanya circuit breaker: setelah terbuka, permintaan berikutnya
     * TIDAK boleh benar-benar keluar. Menghitung permintaan yang lolos adalah
     * satu-satunya cara membuktikannya — nilai kembalian sama-sama false baik
     * karena gateway menolak maupun karena circuit menahan.
     */
    public function test_circuit_menahan_permintaan_setelah_gateway_gagal_tiga_kali(): void
    {
        Http::fake(['*' => Http::response('nope', 500)]);

        $ch = new N8nWhatsAppChannel('http://127.0.0.1:9/send', 'rahasia');

        foreach (range(1, 5) as $ke) {
            $this->assertFalse($ch->send('6281234567890', 'halo'));
        }

        $this->assertSame('open', $ch->getCircuitStatus()['state']);
        $this->assertCount(3, Http::recorded(), 'hanya tiga percobaan pertama yang boleh keluar');
    }

    public function test_gateway_yang_tidak_bisa_dihubungi_juga_membuka_circuit(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $ch = new N8nWhatsAppChannel('http://127.0.0.1:9/send', 'rahasia');

        foreach (range(1, 4) as $ke) {
            $this->assertFalse($ch->send('6281234567890', 'halo'));
        }

        $this->assertSame('open', $ch->getCircuitStatus()['state']);
        $this->assertFalse($ch->isHealthy());
    }

    public function test_payload_ke_gateway_membawa_secret_dan_pengirim(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $ch = new N8nWhatsAppChannel('http://127.0.0.1:3000/send', 'rahasia-secret');
        $ch->send('6281234567890', "PIN Harian: *123456*\nlink", ['type' => 'attendance_magic_link'], '6289999');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return $request->url() === 'http://127.0.0.1:3000/send'
                && $request->header('X-Webhook-Secret') === ['rahasia-secret']
                && $body['from'] === '6289999'
                && $body['to'] === '6281234567890'
                && $body['meta']['type'] === 'attendance_magic_link'
                && str_contains($body['message'], '123456');
        });
    }

    // -- Nomor tujuan -------------------------------------------------------

    /**
     * CATATAN CACAT (tidak dikunci sebagai "benar"): nomor tujuan yang diketik
     * wali kelas di formulir diteruskan APA ADANYA ke gateway — '0857-0000-001'
     * dan bahkan 'bukan-nomor' dikirim tanpa melewati Phone::normalize, lalu
     * tersimpan begitu saja di kolom delivery_target.
     *
     * Test ini merekam keadaan itu supaya perbaikannya nanti terlihat sebagai
     * perubahan yang disengaja, bukan kejutan.
     */
    public function test_nomor_tujuan_dari_formulir_belum_dinormalkan(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        config(['walikelas.whatsapp.driver' => 'log']);

        $user = User::factory()->create(['whatsapp_number' => '6281111111111']);
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $rekam = new class implements NotificationChannel
        {
            public array $terkirim = [];

            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                $this->terkirim[] = $to;

                return true;
            }
        };
        $this->app->instance(NotificationChannel::class, $rekam);

        $this->post(route('classes.attendance.store', $class), [
            'target_number' => '0857-0000-001',
            'send_wa' => '1',
            'force_new' => '1',
        ]);

        $this->assertSame(
            ['0857-0000-001'],
            $rekam->terkirim,
            'perilaku saat ini: tanpa normalisasi. Begitu Phone::normalize dipasang '
            .'di jalur ini, harapannya berubah menjadi 62857000001.',
        );
    }

    /** Kegagalan menghubungi gateway saat polling menandai guru terputus. */
    public function test_polling_menandai_terputus_saat_gateway_tak_terjangkau(): void
    {
        config([
            'walikelas.whatsapp.driver' => 'n8n',
            'walikelas.whatsapp.n8n.session_url' => 'http://127.0.0.1:3000/session',
        ]);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('refused'));

        $user = User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_status' => 'connected',
            'wa_connected_at' => now(),
        ]);
        $this->actingAs($user);

        $this->assertTrue($user->whatsappConnected());

        $this->getJson(route('whatsapp.status'))
            ->assertOk()
            ->assertJson(['status' => 'disconnected']);

        $user->refresh();
        $this->assertSame('disconnected', $user->wa_session_status);
        $this->assertFalse($user->whatsappConnected());
    }
}
