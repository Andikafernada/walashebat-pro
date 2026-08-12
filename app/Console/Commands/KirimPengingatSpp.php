<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppMessage;
use App\Models\Classroom;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Kirim pengingat iuran bulanan ke grup WhatsApp orang tua.
 *
 * Isinya teks bebas yang ditulis wali kelas sendiri — tidak menyebut siapa yang
 * belum membayar. Itu disengaja: menyebut nama anak yang menunggak di grup yang
 * dibaca seluruh orang tua adalah keputusan yang jauh lebih besar daripada
 * teknisnya.
 */
class KirimPengingatSpp extends Command
{
    protected $signature = 'walikelas:kirim-pengingat-spp {--paksa : Abaikan penanda sudah-terkirim bulan ini}';

    protected $description = 'Kirim pengingat iuran bulanan ke grup WhatsApp orang tua';

    public function handle(): int
    {
        $hariIni = Carbon::today();
        $terkirim = 0;
        $dilewati = 0;

        /*
         * withoutTenant(): dijalankan penjadwal, tanpa sesi web. Tanpa ini
         * TenantScope gagal-tertutup dan perintahnya berjalan mulus setiap hari
         * sambil tidak menemukan satu kelas pun — kegagalan yang tidak
         * menghasilkan galat maupun log.
         */
        $kelas = Classroom::withoutTenant()
            ->where('spp_pengingat_aktif', true)
            ->where('is_active', true)
            ->whereNotNull('parent_group_wa')
            ->with('user:id,name')
            ->get();

        foreach ($kelas as $k) {
            if ($k->kelasAjar()) {
                // Iuran kelas urusan wali kelasnya, bukan guru mapel.
                $dilewati++;
                continue;
            }

            if (! $this->jatuhTempoHariIni($k->spp_pengingat_tanggal, $hariIni)) {
                continue;
            }

            /*
             * Penjaga kirim-ganda. Penjadwal bisa dijalankan ulang, dan pesan
             * yang sudah masuk grup tidak bisa ditarik kembali.
             */
            if (! $this->option('paksa')
                && $k->spp_pengingat_terkirim_pada
                && $k->spp_pengingat_terkirim_pada->isSameMonth($hariIni)) {
                $dilewati++;
                continue;
            }

            $pesan = $this->susunPesan($k, $hariIni);

            if (trim($pesan) === '') {
                $dilewati++;
                continue;
            }

            /*
             * userId WAJIB: gerbang masa aktif otomasi di SendWhatsAppMessage
             * dibaca dari sana. Tanpa itu jalur ini akan mengirim WhatsApp
             * untuk akun yang langganannya sudah berakhir.
             */
            SendWhatsAppMessage::dispatch(
                to: $k->parent_group_wa,
                message: $pesan,
                userId: $k->user_id,
                meta: ['jenis' => 'pengingat_spp', 'class_id' => $k->id],
            );

            // Ditandai saat DIANTREKAN, bukan saat terkirim: kalau gateway
            // sedang mati, mengulanginya besok lebih baik daripada mengirim
            // empat kali begitu ia pulih.
            $k->forceFill(['spp_pengingat_terkirim_pada' => $hariIni])->save();

            $terkirim++;
            $this->line("  terkirim: {$k->name}");
        }

        $this->info("Pengingat iuran: {$terkirim} terkirim, {$dilewati} dilewati.");

        return self::SUCCESS;
    }

    /**
     * Tanggal 31 tidak ada di semua bulan.
     *
     * Wali kelas yang memilih tanggal 31 tetap harus dapat pengingat di
     * Februari — kalau tidak, pengingatnya hilang empat bulan setahun tanpa ada
     * yang menyadarinya. Jadi tanggal yang melewati akhir bulan dijatuhkan ke
     * hari terakhir.
     */
    private function jatuhTempoHariIni(int $tanggal, Carbon $hariIni): bool
    {
        $efektif = min($tanggal, $hariIni->daysInMonth);

        return $hariIni->day === $efektif;
    }

    private function susunPesan(Classroom $kelas, Carbon $hariIni): string
    {
        $teks = $kelas->spp_pengingat_teks ?: self::TEKS_BAWAAN;

        return str_replace(
            ['{nama_kelas}', '{bulan}', '{tahun}', '{wali_kelas}'],
            [
                $kelas->name,
                $hariIni->translatedFormat('F'),
                $hariIni->year,
                $kelas->user->name ?? 'Wali Kelas',
            ],
            $teks
        );
    }

    private const TEKS_BAWAAN = "*Pengingat Iuran Kelas {nama_kelas}*\n\n"
        ."Bapak/Ibu orang tua/wali murid yang kami hormati,\n"
        ."kami mengingatkan iuran kelas untuk bulan {bulan} {tahun}.\n\n"
        ."Terima kasih atas perhatian dan kerja samanya.\n\n"
        ."Hormat kami,\n{wali_kelas}";
}
