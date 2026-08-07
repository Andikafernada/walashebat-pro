<?php

namespace Tests\Feature;

use App\Models\CashBook;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * balance_after adalah saldo berjalan: nilainya harus sama dengan jumlah
 * seluruh transaksi sampai baris itu, menurut urutan (transaction_date, id).
 *
 * Dua cara lama merusaknya:
 *  - entri baru selalu diberi saldo akhir kelas, padahal tanggalnya bisa
 *    membuatnya mendarat di tengah urutan;
 *  - saldo minus diklem ke nol saat disimpan, sehingga angkanya tidak pernah
 *    benar untuk kelas yang sedang minus.
 */
class SaldoBukuKasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Classroom $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->user = User::factory()->create();
        $this->class = Classroom::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);
    }

    private function catat(string $tanggal, string $tipe, int $nominal): void
    {
        $this->post(route('classes.cashbook.store', $this->class), [
            'transaction_date' => $tanggal,
            'type' => $tipe,
            'amount' => $nominal,
            'description' => "{$tipe} {$nominal}",
        ])->assertSessionHasNoErrors();
    }

    /** @return array<int, int> balance_after menurut urutan kronologis */
    private function saldoBerurutan(): array
    {
        return CashBook::withoutTenant()
            ->orderBy('transaction_date')->orderBy('id')
            ->pluck('balance_after')->map(fn ($v) => (int) $v)->all();
    }

    public function test_saldo_berjalan_benar_untuk_urutan_maju(): void
    {
        $this->catat('2026-08-01', 'in', 100000);
        $this->catat('2026-08-02', 'out', 30000);
        $this->catat('2026-08-03', 'in', 5000);

        $this->assertSame([100000, 70000, 75000], $this->saldoBerurutan());
    }

    /**
     * Transaksi yang tanggalnya mundur mendarat di TENGAH urutan, dan seluruh
     * entri sesudahnya harus ikut bergeser.
     */
    public function test_transaksi_bertanggal_mundur_menggeser_saldo_sesudahnya(): void
    {
        $this->catat('2026-08-01', 'in', 100000);
        $this->catat('2026-08-03', 'out', 30000);

        // Disisipkan di antara keduanya.
        $this->catat('2026-08-02', 'in', 50000);

        // 100000 -> 150000 -> 120000
        $this->assertSame([100000, 150000, 120000], $this->saldoBerurutan());
    }

    public function test_saldo_minus_dicatat_apa_adanya_bukan_nol(): void
    {
        $this->catat('2026-08-01', 'in', 10000);
        $this->catat('2026-08-02', 'out', 25000);

        $this->assertSame([10000, -15000], $this->saldoBerurutan());
    }

    public function test_saldo_minus_bisa_pulih_kembali(): void
    {
        $this->catat('2026-08-01', 'out', 20000);
        $this->catat('2026-08-02', 'in', 50000);

        $this->assertSame([-20000, 30000], $this->saldoBerurutan());
    }

    public function test_menghapus_transaksi_tengah_menghitung_ulang_sisanya(): void
    {
        $this->catat('2026-08-01', 'in', 100000);
        $this->catat('2026-08-02', 'out', 30000);
        $this->catat('2026-08-03', 'in', 5000);

        $tengah = CashBook::withoutTenant()->orderBy('transaction_date')->skip(1)->first();

        $this->delete(route('classes.cashbook.destroy', [$this->class, $tengah]));

        // Tanpa 30000 keluar: 100000 -> 105000
        $this->assertSame([100000, 105000], $this->saldoBerurutan());
    }

    public function test_menghapus_transaksi_pertama_menghitung_ulang_sisanya(): void
    {
        $this->catat('2026-08-01', 'in', 100000);
        $this->catat('2026-08-02', 'out', 30000);

        $pertama = CashBook::withoutTenant()->orderBy('transaction_date')->first();

        $this->delete(route('classes.cashbook.destroy', [$this->class, $pertama]));

        $this->assertSame([-30000], $this->saldoBerurutan());
    }

    /** Saldo baris terakhir harus sama dengan saldo yang ditampilkan di kartu. */
    public function test_saldo_baris_terakhir_sama_dengan_saldo_kelas(): void
    {
        $this->catat('2026-08-01', 'in', 100000);
        $this->catat('2026-08-02', 'out', 30000);
        $this->catat('2026-08-03', 'out', 90000);

        $terakhir = CashBook::withoutTenant()
            ->orderByDesc('transaction_date')->orderByDesc('id')->first();

        $masuk = (int) CashBook::withoutTenant()->where('type', 'in')->sum('amount');
        $keluar = (int) CashBook::withoutTenant()->where('type', 'out')->sum('amount');

        $this->assertSame($masuk - $keluar, (int) $terakhir->balance_after);
        $this->assertSame(-20000, (int) $terakhir->balance_after);
    }

    /** Kelas lain tidak boleh ikut terhitung ulang. */
    public function test_kelas_lain_tidak_ikut_terpengaruh(): void
    {
        $lain = Classroom::factory()->create(['user_id' => $this->user->id]);

        $this->post(route('classes.cashbook.store', $lain), [
            'transaction_date' => '2026-08-01', 'type' => 'in',
            'amount' => 777000, 'description' => 'kas kelas lain',
        ])->assertSessionHasNoErrors();

        $this->catat('2026-08-01', 'in', 1000);
        $this->catat('2026-08-02', 'out', 400);

        $barisLain = CashBook::withoutTenant()->where('class_id', $lain->id)->sole();
        $this->assertSame(777000, (int) $barisLain->balance_after);

        $milik = CashBook::withoutTenant()->where('class_id', $this->class->id)
            ->orderBy('transaction_date')->orderBy('id')
            ->pluck('balance_after')->map(fn ($v) => (int) $v)->all();

        $this->assertSame([1000, 600], $milik);
    }
}
