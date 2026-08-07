<?php

namespace Database\Seeders;

use App\Models\CashBook;
use App\Models\Classroom;
use App\Models\Holiday;
use App\Models\OrganizationStructure;
use App\Models\Schedule;
use App\Models\Seat;
use App\Models\Student;
use App\Models\User;
use App\Models\ViolationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
         * Seeder ini membuat akun demo berkata sandi lemah. Menjalankannya di
         * produksi berarti membuka pintu masuk yang diketahui semua orang.
         */
        if (app()->environment('production')) {
            $this->command?->error('DatabaseSeeder berisi akun demo dan tidak dijalankan di produksi.');

            return;
        }

        // Wali kelas demo. Login: walas@walas.my.id / password
        $user = User::updateOrCreate(
            ['email' => 'walas@walas.my.id'],
            [
                'name' => 'Andika Fernanda',
                'password' => Hash::make('password'),
                'whatsapp_number' => '6281234567890',
                'school_name' => 'SMK Pasundan 2 Bandung',
                // Driver 'log' di lokal tidak butuh gateway sungguhan.
                'wa_session_status' => 'connected',
                'wa_connected_at' => now(),
            ]
        );

        /*
         * Kolom langganan dan is_active sengaja tidak fillable, jadi tidak bisa
         * dititipkan lewat updateOrCreate di atas — kalau dipaksa, nilainya
         * dibuang diam-diam dan akun demo tidak pernah berstatus PRO.
         */
        $user->forceFill([
            'is_active' => true,
            'subscription_tier' => User::TIER_PRO,
            'subscription_ends_at' => now()->addYear(),
        ])->save();

        // Master jenis pelanggaran.
        foreach ([
            ['Terlambat', 'ringan', -5],
            ['Tidak memakai seragam lengkap', 'ringan', -10],
            ['Membolos', 'sedang', -25],
            ['Prestasi / poin positif', null, 20],
        ] as [$name, $cat, $pts]) {
            ViolationType::updateOrCreate(
                ['user_id' => $user->id, 'name' => $name],
                ['category' => $cat, 'points' => $pts]
            );
        }

        // Kelas demo.
        $class = Classroom::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'XII RPL 1'],
            [
                'academic_year' => '2025/2026',
                'major' => 'RPL',
                'homeroom_wa' => '6281234567890',
                'auto_attendance' => true,
            ]
        );

        // Siswa.
        $names = ['Ahmad Fauzi', 'Bunga Lestari', 'Cahya Nugraha', 'Dewi Anggraini', 'Eko Prasetyo',
                  'Fitri Handayani', 'Galih Ramadhan', 'Hana Safitri', 'Irfan Maulana', 'Jihan Aulia'];
        $students = collect($names)->map(function ($name, $i) use ($user, $class) {
            return Student::updateOrCreate(
                ['class_id' => $class->id, 'nis' => '2025'.str_pad($i + 1, 3, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $user->id,
                    'name' => $name,
                    'gender' => $i % 2 === 0 ? 'L' : 'P',
                    'phone' => '62813000'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'parent_phone' => '62812000'.str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'discipline_points' => 100,
                ]
            );
        });

        // Struktur organisasi (Seksi Absensi = penerima magic link).
        $roleMap = ['ketua' => 0, 'sekretaris' => 1, 'bendahara' => 2, 'seksi_absensi' => 3];
        foreach ($roleMap as $role => $idx) {
            OrganizationStructure::updateOrCreate(
                ['user_id' => $user->id, 'class_id' => $class->id, 'role' => $role],
                ['student_id' => $students[$idx]->id, 'sort_order' => $idx]
            );
        }

        // Jadwal Senin.
        foreach ([
            // Contoh sistem blok: Senin mulai 07:00, Selasa mulai 09:30.
            [1, 'Pemrograman Web', 'Andika Fernanda', '07:00', '09:00'],
            [1, 'Basis Data', 'Rina Marlina', '09:15', '11:00'],
            [2, 'Produk Kreatif', 'Andika Fernanda', '09:30', '12:00'],
        ] as [$day, $subject, $teacher, $start, $end]) {
            Schedule::updateOrCreate(
                ['user_id' => $user->id, 'class_id' => $class->id, 'day_of_week' => $day, 'subject' => $subject],
                ['teacher_name' => $teacher, 'start_time' => $start, 'end_time' => $end]
            );
        }

        // Buku kas.
        $balance = 0;
        foreach ([
            ['in', 500000, 'Iuran kas kelas Januari'],
            ['out', 150000, 'Beli spidol & alat kebersihan'],
        ] as [$type, $amount, $desc]) {
            $balance += $type === 'in' ? $amount : -$amount;
            CashBook::create([
                'user_id' => $user->id, 'class_id' => $class->id,
                'transaction_date' => now()->toDateString(),
                'type' => $type, 'amount' => $amount, 'description' => $desc,
                'balance_after' => $balance,
            ]);
        }

        // Denah 2x5 diisi 10 siswa pertama.
        Seat::where('class_id', $class->id)->delete();
        $i = 0;
        for ($r = 0; $r < 2; $r++) {
            for ($c = 0; $c < 5; $c++) {
                Seat::create([
                    'user_id' => $user->id, 'class_id' => $class->id,
                    'student_id' => $students[$i]->id ?? null,
                    'row_index' => $r, 'col_index' => $c,
                ]);
                $i++;
            }
        }

        // Contoh hari libur nasional (user_id NULL = berlaku semua tenant).
        // Scheduler menahan pembuatan sesi absensi pada rentang ini.
        Holiday::updateOrCreate(
            ['user_id' => null, 'start_date' => now()->startOfYear()->addMonths(4)->toDateString()],
            [
                'end_date' => now()->startOfYear()->addMonths(4)->addDays(6)->toDateString(),
                'description' => 'Contoh: Libur Idul Fitri',
            ]
        );

        // Seed dimensi karakter Profil Pelajar Pancasila.
        $this->call(CharacterDimensionSeeder::class);

        $this->command?->info('Seed selesai. Login: walas@walas.my.id / password');
    }
}
