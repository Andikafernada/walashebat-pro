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

class Audit02WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    /** ---------- 1. Phone::normalize ---------- */
    public function test_audit_phone_normalize(): void
    {
        $kasus = [
            '081234567890',
            '+62 812-3456-7890',
            '62 812 3456 7890',
            '8123456789',
            '0812 3456 7890 ',
            '021-1234567',
            '  ',
            '',
            'abcdefg',
            '0',
            '00',
            '08',
            '123',
            '+1 555 0100',
            '0812345678901234567890',
            "0812\n3456",
            '<script>0812345</script>',
            '0812-3456-7890 (rumah)',
        ];

        foreach ($kasus as $k) {
            fwrite(STDERR, sprintf(
                "PHONE  in=%-32s out=%s\n",
                var_export($k, true),
                var_export(Phone::normalize($k), true)
            ));
        }
        $this->assertTrue(true);
    }

    /** ---------- 2. Dampak sync queue pada penanganan error job ---------- */
    public function test_audit_job_gagal_pada_queue_sync(): void
    {
        config(['walikelas.whatsapp.driver' => 'log']);

        $user = User::factory()->create(['whatsapp_number' => '6281111111111']);
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        // Channel yang selalu menolak (mensimulasikan gateway mati / circuit open)
        $this->app->instance(NotificationChannel::class, new class implements NotificationChannel {
            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                return false;
            }
        });

        $svc = app(AttendanceSessionService::class);
        ['session' => $session, 'pin' => $pin] = $svc->create(classroom: $class);

        $lempar = null;
        try {
            $svc->dispatchMagicLink($session, '6282222222222', $pin, $user->whatsapp_number);
        } catch (\Throwable $e) {
            $lempar = $e;
        }

        $session->refresh();
        fwrite(STDERR, sprintf(
            "JOBSYNC exception=%s msg=%s | delivery_status=%s delivery_error=%s delivered_at=%s\n",
            $lempar ? get_class($lempar) : 'NONE',
            $lempar ? $lempar->getMessage() : '-',
            var_export($session->delivery_status, true),
            var_export($session->delivery_error, true),
            var_export($session->delivered_at, true)
        ));

        fwrite(STDERR, 'JOBSYNC failed_jobs='.\Illuminate\Support\Facades\DB::table('failed_jobs')->count()."\n");
        $this->assertTrue(true);
    }

    /** Sama, tapi lewat HTTP controller — apa yang dilihat guru? */
    public function test_audit_controller_saat_gateway_menolak(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        config(['walikelas.whatsapp.driver' => 'log']);

        $user = User::factory()->create(['whatsapp_number' => '6281111111111']);
        $class = Classroom::factory()->create(['user_id' => $user->id, 'homeroom_wa' => '6282222222222']);
        $this->actingAs($user);

        $this->app->instance(NotificationChannel::class, new class implements NotificationChannel {
            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                return false;
            }
        });

        $resp = $this->post(route('classes.attendance.store', $class), [
            'target_number' => '6282222222222',
            'send_wa' => '1',
        ]);

        fwrite(STDERR, 'CTRL status='.$resp->getStatusCode()."\n");
        $sess = AttendanceSession::withoutTenant()->latest('id')->first();
        fwrite(STDERR, 'CTRL session_delivery_status='.var_export($sess?->delivery_status, true)
            .' error='.var_export($sess?->delivery_error, true)."\n");
        $this->assertTrue(true);
    }

    /** ---------- 3. Webhook masuk ---------- */
    public function test_audit_webhook_autentikasi(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => 'rahasia-benar']);

        $user = User::factory()->create([
            'wa_session_id' => 'guru-99',
            'wa_session_status' => 'disconnected',
        ]);

        $payload = ['session_id' => 'guru-99', 'status' => 'connected'];

        $tanpa = $this->postJson('/api/webhooks/whatsapp-session', $payload);
        $salah = $this->withHeader('X-Webhook-Secret', 'salah')->postJson('/api/webhooks/whatsapp-session', $payload);
        $benar = $this->withHeader('X-Webhook-Secret', 'rahasia-benar')->postJson('/api/webhooks/whatsapp-session', $payload);

        fwrite(STDERR, "WEBHOOK tanpa-secret={$tanpa->getStatusCode()} secret-salah={$salah->getStatusCode()} secret-benar={$benar->getStatusCode()}\n");

        $user->refresh();
        fwrite(STDERR, 'WEBHOOK status_setelah='.var_export($user->wa_session_status, true)
            .' connected_at='.var_export((string) $user->wa_connected_at, true)."\n");

        // Replay: kirim lagi payload identik berkali-kali
        for ($i = 0; $i < 3; $i++) {
            $r = $this->withHeader('X-Webhook-Secret', 'rahasia-benar')->postJson('/api/webhooks/whatsapp-session', $payload);
            fwrite(STDERR, "WEBHOOK replay#{$i}={$r->getStatusCode()}\n");
        }

        // Payload aneh / injeksi
        $aneh = $this->withHeader('X-Webhook-Secret', 'rahasia-benar')->postJson('/api/webhooks/whatsapp-session', [
            'session_id' => "guru-99' OR 1=1 --",
            'status' => 'connected',
        ]);
        fwrite(STDERR, "WEBHOOK sqli={$aneh->getStatusCode()} body=".$aneh->getContent()."\n");

        $arr = $this->withHeader('X-Webhook-Secret', 'rahasia-benar')->postJson('/api/webhooks/whatsapp-session', [
            'session_id' => ['a' => 'b'],
            'status' => 'connected',
        ]);
        fwrite(STDERR, "WEBHOOK session_id-array={$arr->getStatusCode()}\n");

        $xss = $this->withHeader('X-Webhook-Secret', 'rahasia-benar')->postJson('/api/webhooks/whatsapp-session', [
            'session_id' => 'guru-99',
            'status' => 'disconnected',
            'error' => '<script>alert(1)</script>',
        ]);
        $user->refresh();
        fwrite(STDERR, "WEBHOOK xss={$xss->getStatusCode()} tersimpan=".var_export($user->wa_last_error, true)."\n");

        $this->assertTrue(true);
    }

    /** Secret kosong di config => harus tolak semua. */
    public function test_audit_webhook_secret_kosong(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => null]);
        User::factory()->create(['wa_session_id' => 'guru-1']);

        $r1 = $this->postJson('/api/webhooks/whatsapp-session', ['session_id' => 'guru-1', 'status' => 'connected']);
        $r2 = $this->withHeader('X-Webhook-Secret', '')->postJson('/api/webhooks/whatsapp-session', ['session_id' => 'guru-1', 'status' => 'connected']);

        fwrite(STDERR, "WEBHOOK-KOSONG tanpa-header={$r1->getStatusCode()} header-kosong={$r2->getStatusCode()}\n");
        $this->assertTrue(true);
    }

    /** Session id tebakan: guru-{id} — bisa menyasar guru lain. */
    public function test_audit_webhook_session_id_tertebak(): void
    {
        config(['walikelas.whatsapp.n8n.secret' => 'rahasia']);

        $korban = User::factory()->create([
            'wa_session_id' => 'guru-'.User::max('id') + 1,
            'wa_session_status' => 'connected',
            'wa_connected_at' => now(),
        ]);
        $korban->update(['wa_session_id' => 'guru-'.$korban->id]);

        $r = $this->withHeader('X-Webhook-Secret', 'rahasia')->postJson('/api/webhooks/whatsapp-session', [
            'session_id' => 'guru-'.$korban->id,
            'status' => 'disconnected',
            'error' => 'dipaksa putus oleh pihak ketiga',
        ]);

        $korban->refresh();
        fwrite(STDERR, "WEBHOOK-TEBAK http={$r->getStatusCode()} status=".var_export($korban->wa_session_status, true)
            .' connected_at='.var_export($korban->wa_connected_at, true)."\n");
        $this->assertTrue(true);
    }

    /** ---------- 4. CircuitBreaker ---------- */
    public function test_audit_circuit_breaker_buka_tutup(): void
    {
        $cb = new CircuitBreaker('audit-cb', failureThreshold: 3, resetTimeout: 60, successThreshold: 2);

        fwrite(STDERR, 'CB awal='.json_encode($cb->getStatus())."\n");
        $cb->recordFailure();
        $cb->recordFailure();
        fwrite(STDERR, 'CB 2 gagal='.json_encode($cb->getStatus()).' available='.var_export($cb->isAvailable(), true)."\n");
        $cb->recordFailure();
        fwrite(STDERR, 'CB 3 gagal='.json_encode($cb->getStatus()).' available='.var_export($cb->isAvailable(), true)."\n");

        // instance baru: apakah state persisten lewat cache?
        $cb2 = new CircuitBreaker('audit-cb', failureThreshold: 3, resetTimeout: 60, successThreshold: 2);
        fwrite(STDERR, 'CB instance-baru='.json_encode($cb2->getStatus()).' available='.var_export($cb2->isAvailable(), true)."\n");

        // Paksa waktu maju: geser opened_at ke masa lalu
        cache()->put('circuit_breaker:audit-cb:opened_at', time() - 61, 600);
        fwrite(STDERR, 'CB setelah 61s available='.var_export($cb2->isAvailable(), true).' status='.json_encode($cb2->getStatus())."\n");

        // Dalam HALF_OPEN: berapa request yang diizinkan?
        $izin = 0;
        for ($i = 0; $i < 10; $i++) {
            if ($cb2->isAvailable()) {
                $izin++;
            }
        }
        fwrite(STDERR, "CB half_open mengizinkan {$izin}/10 request (komentar kode: 'izinkan 1 request')\n");

        $cb2->recordSuccess();
        fwrite(STDERR, 'CB half_open +1 sukses='.json_encode($cb2->getStatus())."\n");
        $cb2->recordSuccess();
        fwrite(STDERR, 'CB half_open +2 sukses='.json_encode($cb2->getStatus())."\n");

        $this->assertTrue(true);
    }

    /** TTL: apakah OPEN diam-diam jadi CLOSED tanpa lewat HALF_OPEN? */
    public function test_audit_circuit_breaker_ttl(): void
    {
        $cb = new CircuitBreaker('audit-ttl', failureThreshold: 3, resetTimeout: 60);
        $cb->trip();
        fwrite(STDERR, 'CBTTL setelah trip='.json_encode($cb->getStatus())."\n");

        // Simulasikan kunci 'state' & 'opened_at' kedaluwarsa (TTL 120s) sementara
        // 'failures' (TTL 300s) masih hidup.
        $cb->recordFailure();
        cache()->forget('circuit_breaker:audit-ttl:state');
        cache()->forget('circuit_breaker:audit-ttl:opened_at');

        fwrite(STDERR, 'CBTTL setelah state&opened_at kedaluwarsa='.json_encode($cb->getStatus())
            .' available='.var_export($cb->isAvailable(), true)."\n");

        // failures masih tersisa -> satu kegagalan berikutnya langsung trip lagi?
        $cb->recordFailure();
        fwrite(STDERR, 'CBTTL +1 kegagalan='.json_encode($cb->getStatus())."\n");

        // opened_at hilang tapi state masih open -> macet?
        $cb2 = new CircuitBreaker('audit-macet', failureThreshold: 3, resetTimeout: 60);
        $cb2->trip();
        cache()->forget('circuit_breaker:audit-macet:opened_at');
        fwrite(STDERR, 'CBMACET state=open tanpa opened_at -> available='
            .var_export($cb2->isAvailable(), true).' status='.json_encode($cb2->getStatus())."\n");

        $this->assertTrue(true);
    }

    /** Circuit breaker n8n: benarkah membuka setelah 3 kegagalan HTTP? */
    public function test_audit_n8n_channel_membuka_circuit(): void
    {
        Http::fake(['*' => Http::response('nope', 500)]);

        $ch = new N8nWhatsAppChannel('http://127.0.0.1:9/send', 'rahasia');

        for ($i = 1; $i <= 5; $i++) {
            $ok = $ch->send('6281234567890', 'halo');
            fwrite(STDERR, "N8N kirim#{$i} hasil=".var_export($ok, true)
                .' circuit='.json_encode($ch->getCircuitStatus())."\n");
        }
        fwrite(STDERR, 'N8N jumlah HTTP request yang benar-benar keluar='.count(Http::recorded())."\n");

        $this->assertTrue(true);
    }

    /** Circuit breaker n8n saat gateway benar-benar mati (connection refused). */
    public function test_audit_n8n_channel_gateway_mati(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $ch = new N8nWhatsAppChannel('http://127.0.0.1:9/send', 'rahasia');
        for ($i = 1; $i <= 4; $i++) {
            $ok = $ch->send('6281234567890', 'halo');
            fwrite(STDERR, "N8NMATI kirim#{$i} hasil=".var_export($ok, true)
                .' state='.($ch->getCircuitStatus()['state'])."\n");
        }
        fwrite(STDERR, 'N8NMATI isHealthy='.var_export($ch->isHealthy(), true)."\n");
        $this->assertTrue(true);
    }

    /** target_number dari form TIDAK dinormalisasi Phone::normalize. */
    public function test_audit_target_number_tidak_dinormalisasi(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        config(['walikelas.whatsapp.driver' => 'log']);

        $user = User::factory()->create(['whatsapp_number' => '6281111111111']);
        $class = Classroom::factory()->create(['user_id' => $user->id]);
        $this->actingAs($user);

        $rekam = new class implements NotificationChannel {
            public array $terkirim = [];

            public function send(string $to, string $message, array $meta = [], ?string $from = null): bool
            {
                $this->terkirim[] = $to;

                return true;
            }
        };
        $this->app->instance(NotificationChannel::class, $rekam);

        foreach (['0857-0000-001', '  0812 3456 7890', 'bukan-nomor', '+62 857 0000 001'] as $input) {
            $this->post(route('classes.attendance.store', $class), [
                'target_number' => $input,
                'send_wa' => '1',
                'force_new' => '1',
            ]);
        }

        fwrite(STDERR, 'TARGET yang benar-benar dikirim ke channel = '.json_encode($rekam->terkirim)."\n");
        fwrite(STDERR, 'TARGET delivery_target tersimpan = '.json_encode(
            AttendanceSession::withoutTenant()->pluck('delivery_target')->all()
        )."\n");

        $this->assertTrue(true);
    }

    /** Polling status: kegagalan gateway sesaat menulis 'disconnected' ke DB. */
    public function test_audit_polling_status_menandai_guru_terputus(): void
    {
        config(['walikelas.whatsapp.driver' => 'n8n', 'walikelas.whatsapp.n8n.session_url' => 'http://127.0.0.1:3000/session']);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('refused'));

        $user = User::factory()->create([
            'whatsapp_number' => '6281234567890',
            'wa_session_status' => 'connected',
            'wa_connected_at' => now(),
        ]);
        $this->actingAs($user);

        fwrite(STDERR, 'POLLING sebelum: connected='.var_export($user->whatsappConnected(), true)."\n");

        $r = $this->getJson(route('whatsapp.status'));
        $user->refresh();

        fwrite(STDERR, 'POLLING http='.$r->getStatusCode().' body='.$r->getContent()."\n");
        fwrite(STDERR, 'POLLING sesudah: wa_session_status='.var_export($user->wa_session_status, true)
            .' wa_connected_at='.var_export((string) $user->wa_connected_at, true)
            .' whatsappConnected()='.var_export($user->whatsappConnected(), true)."\n");

        $this->assertTrue(true);
    }

    /** Apa yang dikirim ke n8n? Cek payload & header. */
    public function test_audit_payload_ke_n8n(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $ch = new N8nWhatsAppChannel('http://127.0.0.1:3000/send', 'rahasia-secret');
        $ch->send('6281234567890', "PIN Harian: *123456*\nlink", ['type' => 'attendance_magic_link'], '6289999');

        foreach (Http::recorded() as [$req, $res]) {
            fwrite(STDERR, 'N8NPAYLOAD url='.$req->url()."\n");
            fwrite(STDERR, 'N8NPAYLOAD headers='.json_encode($req->headers())."\n");
            fwrite(STDERR, 'N8NPAYLOAD body='.$req->body()."\n");
        }
        $this->assertTrue(true);
    }
}
