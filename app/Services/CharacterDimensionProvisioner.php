<?php

namespace App\Services;

use App\Models\CharacterDimension;

/**
 * Menyiapkan enam dimensi Profil Pelajar Pancasila untuk seorang wali kelas.
 *
 * Seluruh fitur Jurnal & Portofolio Karakter berdiri di atas daftar ini:
 * pencatatan karakter, refleksi mandiri siswa, sampai formulir refleksi publik.
 * Bila daftarnya kosong, halaman-halaman itu tampil tanpa pilihan apa pun dan
 * tidak bisa dikirim. Karena itu penyiapannya dilakukan sejak pendaftaran,
 * bukan menunggu wali kelas mengisinya sendiri.
 */
class CharacterDimensionProvisioner
{
    /**
     * Enam dimensi bawaan sesuai Profil Pelajar Pancasila.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            [
                'code' => 'imtak',
                'name' => 'Beriman, Bertakwa Tuhan YME, dan Berakhlak Mulia',
                'name_en' => 'Faith, Piety, and Noble Character',
                'description' => 'Mengembangkan hubungan yang sehat dengan Tuhan YME, berperilaku sopan, rendah hati, toleran terhadap perbedaan agama dan kepercayaan.',
                'indicators' => [
                    'Beribadah tepat waktu',
                    'Menjaga lingkungan ibadah',
                    'Menghormati perbedaan agama',
                    'Berperilaku sopan dan santun',
                    'Menjauhi larangan agama',
                ],
                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                'color' => '#f59e0b',
                'sort_order' => 1,
            ],
            [
                'code' => 'kebinekaan',
                'name' => 'Berkebinekaan Global',
                'name_en' => 'Global and Cultural Diversity',
                'description' => 'Memahami dan menghargai budaya, etnis, agama, dan kepercayaan lain sebagai kekayaan bangsa Indonesia.',
                'indicators' => [
                    'Menghormati perbedaan budaya',
                    'Menghormati teman beda agama',
                    'Tidak membeda-bedakan teman',
                    'Ikut kegiatan lintas budaya',
                    'Menjaga kerukunan',
                ],
                'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'color' => '#8b5cf6',
                'sort_order' => 2,
            ],
            [
                'code' => 'gotong_royong',
                'name' => 'Bergotong Royong',
                'name_en' => 'Cooperation',
                'description' => 'Kolaborasi dalam kehidupan bermasyarakat, berbangsa, dan bernegara untuk kepentingan bersama.',
                'indicators' => [
                    'Suka membantu teman',
                    'Ikut kerja bakti',
                    'Berbagi tugas kelompok',
                    'Menerima pendapat orang lain',
                    'Menjadi pendengar yang baik',
                ],
                'icon' => 'M17 20h5v-1a4 4 0 00-3.417-3.582l-2.5 1.25a4.5 4.5 0 00-3.583 3.417V20M7 20h1m-1-4h18M7 20H3M7 16v4M17 16v4M7 8a4 4 0 014-4h2a4 4 0 014 4v1M7 12v4',
                'color' => '#10b981',
                'sort_order' => 3,
            ],
            [
                'code' => 'mandiri',
                'name' => 'Mandiri',
                'name_en' => 'Independence',
                'description' => 'Bertanggung jawab atas proses dan hasil belajarnya, serta dapat memanfaatkan lingkungan secara optimal.',
                'indicators' => [
                    'Mengerjakan tugas sendiri',
                    'Datang tepat waktu',
                    'Mengatur waktu dengan baik',
                    'Bertanggung jawab atas tugas',
                    'Tidak bergantung pada orang lain',
                ],
                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'color' => '#3b82f6',
                'sort_order' => 4,
            ],
            [
                'code' => 'nalar_kritis',
                'name' => 'Bernalar Kritis',
                'name_en' => 'Critical Thinking',
                'description' => 'Melakukan proses berpikir untuk memecahkan masalah dan mengambil keputusan secara objektif.',
                'indicators' => [
                    'Bertanya dengan baik',
                    'Mengumpulkan informasi',
                    'Menganalisis masalah',
                    'Menyampaikan pendapat',
                    'Menemukan solusi',
                ],
                'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
                'color' => '#f97316',
                'sort_order' => 5,
            ],
            [
                'code' => 'kreatif',
                'name' => 'Kreatif',
                'name_en' => 'Creativity',
                'description' => 'Menghasilkan sesuatu orisinal, baru, dan bermakna dari proses belajar dan pengembangan diri.',
                'indicators' => [
                    'Menghasilkan ide baru',
                    'Menyelesaikan masalah dengan cara inovatif',
                    'Mengembangkan karya kreatif',
                    'Berani mencoba hal baru',
                    'Menunjukkan orisinalitas',
                ],
                'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                'color' => '#ec4899',
                'sort_order' => 6,
            ],
        ];
    }

    /**
     * Siapkan dimensi bawaan milik seorang wali kelas.
     *
     * Aman dipanggil berulang: dimensi yang sudah ada tidak ditimpa, sehingga
     * penyesuaian nama, warna, maupun indikator yang dilakukan wali kelas tetap
     * utuh. Yang dikembalikan hanya jumlah dimensi yang benar-benar dibuat.
     */
    public function provisionFor(int $userId): int
    {
        $sudahAda = CharacterDimension::where('user_id', $userId)->pluck('code')->all();

        $dibuat = 0;

        foreach (static::defaults() as $dimensi) {
            if (in_array($dimensi['code'], $sudahAda, true)) {
                continue;
            }

            CharacterDimension::create($dimensi + [
                'user_id' => $userId,
                // Bobot poin dibuat seragam dan bisa disesuaikan wali kelas per
                // dimensi; angkanya sama dengan yang selama ini dipakai.
                'positive_score' => 5,
                'negative_score' => -5,
                'is_active' => true,
            ]);

            $dibuat++;
        }

        return $dibuat;
    }
}
