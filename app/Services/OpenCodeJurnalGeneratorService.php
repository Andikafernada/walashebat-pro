<?php

namespace App\Services;

class OpenCodeJurnalGeneratorService
{
    /**
     * Generate paket lengkap Jurnal Mengajar Kurikulum Merdeka secara instan.
     */
    public function generate(string $subject, string $topic, int $meeting = 1, ?string $className = null): array
    {
        $subjectClean = trim($subject);
        $topicClean = trim($topic);

        $tujuanPembelajaran = $this->formulateTP($subjectClean, $topicClean);
        $aktivitas = $this->formulateAktivitas($subjectClean, $topicClean);
        $p5 = $this->resolveP5Dimension($subjectClean, $topicClean);
        $refleksi = $this->formulateRefleksi($topicClean);

        return [
            'subject' => $subjectClean,
            'topic' => $topicClean,
            'meeting_number' => $meeting,
            'learning_objective' => $tujuanPembelajaran,
            'activity' => $aktivitas,
            'p5_dimension' => $p5,
            'reflection' => $refleksi,
        ];
    }

    /**
     * Rumuskan Tujuan Pembelajaran (TP) berbasis Taksonomi Bloom & Kurikulum Merdeka.
     */
    private function formulateTP(string $subject, string $topic): string
    {
        return "1. Peserta didik mampu memahami dan menjelaskan konsep dasar {$topic} dengan tepat dan terstruktur.\n" .
               "2. Peserta didik mampu menganalisis serta mengidentifikasi penerapan {$topic} dalam konteks kehidupan sehari-hari.\n" .
               "3. Peserta didik mampu mempraktikkan/menyelesaikan studi kasus terkait {$topic} secara mandiri maupun kolaboratif.";
    }

    /**
     * Rumuskan Langkah-langkah Pembelajaran (Pendahuluan, Inti, Penutup).
     */
    private function formulateAktivitas(string $subject, string $topic): string
    {
        return "A. Kegiatan Pendahuluan (15 Menit):\n" .
               "   - Guru membuka kelas dengan salam, doa bersama, dan presensi siswa.\n" .
               "   - Memberikan apersepsi dan pertanyaan pemantik terkait materi {$topic}.\n" .
               "   - Menyampaikan tujuan pembelajaran dan alur aktivitas hari ini.\n\n" .
               "B. Kegiatan Inti (55 Menit):\n" .
               "   - Guru memaparkan materi inti mengenai {$topic} menggunakan media interaktif/kontekstual.\n" .
               "   - Peserta didik berdiskusi dalam kelompok kecil untuk menyelesaikan lembar kerja / studi kasus.\n" .
               "   - Perwakilan kelompok mempresentasikan hasil diskusi di depan kelas, disusul tanggapan kelompok lain.\n" .
               "   - Guru memberikan umpan balik, penguatan konsep, dan klarifikasi miskonsepsi.\n\n" .
               "C. Kegiatan Penutup (10 Menit):\n" .
               "   - Peserta didik bersama guru menyimpulkan poin-poin utama materi {$topic}.\n" .
               "   - Guru melakukan refleksi singkat dan memberikan asesmen formatif / tugas tindak lanjut.\n" .
               "   - Pembelajaran ditutup dengan doa dan salam penutup.";
    }

    /**
     * Tentukan Dimensi Profil Pelajar Pancasila (P5) yang relevan.
     */
    private function resolveP5Dimension(string $subject, string $topic): string
    {
        $map = [
            'informatika' => 'Bernalar Kritis & Kreatif',
            'matematika' => 'Bernalar Kritis & Mandiri',
            'ipa' => 'Bernalar Kritis & Gotong Royong',
            'ips' => 'Berkebinekaan Global & Gotong Royong',
            'bahasa indonesia' => 'Kreatif & Mandiri',
            'bahasa inggris' => 'Berkebinekaan Global & Komunikatif',
            'pjok' => 'Gotong Royong & Mandiri',
            'pai' => 'Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia',
            'pkn' => 'Berkebinekaan Global & Gotong Royong',
            'seni' => 'Kreatif & Mandiri',
        ];

        $lower = strtolower($subject);
        foreach ($map as $key => $dim) {
            if (str_contains($lower, $key)) {
                return $dim;
            }
        }

        return 'Bernalar Kritis & Gotong Royong';
    }

    /**
     * Rumuskan Refleksi & Tindak Lanjut Guru.
     */
    private function formulateRefleksi(string $topic): string
    {
        return "Mayoritas peserta didik antusias dan aktif mengikuti alur pembelajaran mengenai {$topic}. Sebagian besar siswa telah mencapai kriteria ketercapaian tujuan pembelajaran (KKTP). Bagi 2-3 siswa yang masih membutuhkan bimbingan tambahan pada pemahaman konsep, diberikan pendampingan tutorial sebaya pada pertemuan berikutnya.";
    }
}
