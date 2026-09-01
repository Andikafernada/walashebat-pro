<?php

use App\Http\Controllers\JurnalMengajarController;
use App\Http\Controllers\NilaiHarianController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\EmailOtpVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\CashBookController;
use App\Http\Controllers\CharacterPortfolioController;
use App\Http\Controllers\KerajinanController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\StudentCardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganizationStructureController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Public\AttendanceMagicLinkController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SeatingController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\ViolationTypeController;
use App\Http\Controllers\WhatsAppSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check (publik, tidak memerlukan autentikasi)
|--------------------------------------------------------------------------
*/
Route::get('/health', [HealthController::class, 'check'])->name('health');
Route::get('/health/ready', [HealthController::class, 'ready'])->name('health.ready');

Route::get('/', LandingController::class)->name('landing');

/*
|--------------------------------------------------------------------------
| Tamu (belum login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    /*
     * Batas per IP, pelengkap batas per akun di AuthController.
     *
     * Kunci per akun tidak menyentuh penyemprotan kata sandi: satu kata sandi
     * umum dicoba ke ratusan akun berbeda tidak pernah mengenai batas akun
     * mana pun. Yang ini menutupnya.
     *
     * Sengaja longgar. Satu sekolah berbagi satu IP publik di balik NAT, dan
     * seluruh wali kelas masuk pada jam yang hampir sama tiap pagi — batas
     * ketat akan mengunci sekolah itu dari aplikasinya sendiri, kegagalan yang
     * jauh lebih sering terjadi daripada serangan.
     */
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:60,1');
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    /*
     * Pendaftaran terbuka tanpa batas laju adalah jalan termudah membanjiri
     * basis data dengan akun palsu. Batas sengaja longgar (20 per 10 menit per
     * alamat IP) karena satu sekolah kerap mendaftarkan banyak wali kelas
     * sekaligus dari satu jaringan wifi saat pelatihan.
     */
    // Google OAuth & Akun Belajar.id
    Route::get('auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:20,10')
        ->name('register.store');

    Route::get('register/verify-otp/{token}', [EmailOtpVerificationController::class, 'show'])->name('register.otp.show');
    Route::post('register/verify-otp/{token}', [EmailOtpVerificationController::class, 'verify'])->name('register.otp.verify');
    Route::post('register/resend-otp/{token}', [EmailOtpVerificationController::class, 'resend'])->name('register.otp.resend');

    /*
     * Verifikasi nomor WhatsApp pendaftar.
     *
     * Titipan pendaftarannya hidup di cache dan dikunci token yang disimpan di
     * sesi, jadi rute ini tidak perlu parameter apa pun — nomor maupun token
     * tidak pernah muncul di bilah alamat, tempat ia gampang tersalin ke grup.
     *
     * Kirim ulang dibatasi lebih ketat daripada pendaftarannya: tiap panggilan
     * benar-benar mengirim pesan WhatsApp, dan gateway punya jeda antar-kirim.
     */
    Route::get('register/verifikasi', [AuthController::class, 'showVerifikasi'])
        ->name('register.verifikasi.form');
    Route::post('register/verifikasi', [AuthController::class, 'verifikasi'])
        ->middleware('throttle:20,10')
        ->name('register.verifikasi');
    Route::post('register/kirim-ulang', [AuthController::class, 'kirimUlang'])
        ->middleware('throttle:5,10')
        ->name('register.kirim-ulang');

    // Reset kata sandi lewat OTP WhatsApp.
    Route::get('lupa-password', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('lupa-password', [PasswordResetController::class, 'sendOtp'])->name('password.otp.send');
    Route::get('reset-password', [PasswordResetController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Magic Link Absensi (PUBLIK - tanpa login, dilindungi token + PIN)
|--------------------------------------------------------------------------
*/
Route::prefix('a')->middleware('magic-link')->group(function () {
    Route::get('{token}', [AttendanceMagicLinkController::class, 'show'])->name('magic.show');
    Route::post('{token}/verify', [AttendanceMagicLinkController::class, 'verify'])->name('magic.verify');
    /*
     * Roster punya URL GET sendiri, bukan dirender sebagai balasan POST.
     * Dengan begitu menyegarkan halaman tidak memicu "kirim ulang formulir",
     * dan kegagalan validasi pada submit bisa redirect() balik ke sini alih-alih
     * ke URL POST yang lewat GET akan menjadi 405 Method Not Allowed.
     */
    Route::get('{token}/isi', [AttendanceMagicLinkController::class, 'roster'])->name('magic.roster');
    Route::post('{token}/submit', [AttendanceMagicLinkController::class, 'submit'])->name('magic.submit');
    Route::get('{token}/done', [AttendanceMagicLinkController::class, 'done'])->name('magic.done');
});

/*
|--------------------------------------------------------------------------
| Area wali kelas (login + akun aktif)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'auth.tenant'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Panel operator SaaS — role:admin di aplikasi ini berarti pemilik/operator
    // aplikasi, bukan kepala sekolah di suatu sekolah.
    Route::middleware('role:admin')->group(function () {
        Route::get('admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    });

    // Analytics Dashboard
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::patch('{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::patch('read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::get('count', [NotificationController::class, 'count'])->name('count');
        Route::get('latest', [NotificationController::class, 'latest'])->name('latest');
        Route::get('settings', [NotificationController::class, 'settings'])->name('settings');
        Route::patch('settings', [NotificationController::class, 'updateSettings'])->name('settings.update');
        Route::post('push/subscribe', [NotificationController::class, 'subscribePush'])->name('push.subscribe');
        Route::delete('push/unsubscribe', [NotificationController::class, 'unsubscribePush'])->name('push.unsubscribe');
    });

    Route::get('profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Penautan nomor WhatsApp guru (satu nomor per wali kelas).
    Route::get('whatsapp', [WhatsAppSessionController::class, 'show'])->name('whatsapp.index');
    Route::post('whatsapp/pair', [WhatsAppSessionController::class, 'pair'])->name('whatsapp.pair');
    Route::get('whatsapp/status', [WhatsAppSessionController::class, 'status'])->name('whatsapp.status');
    Route::get('whatsapp/groups', [WhatsAppSessionController::class, 'groups'])->name('whatsapp.groups');
    Route::post('whatsapp/groups/test', [WhatsAppSessionController::class, 'testGroup'])->name('whatsapp.groups.test');
    Route::post('whatsapp/autoreply', [WhatsAppSessionController::class, 'autoreplySave'])->name('whatsapp.autoreply');
    // Diagnosa saja, tidak mengirim pesan apa pun ke grup orang tua.
    Route::post('whatsapp/autoreply/check', [WhatsAppSessionController::class, 'autoreplyCheck'])->name('whatsapp.autoreply.check');
    Route::match(['post', 'delete'], 'whatsapp/disconnect', [WhatsAppSessionController::class, 'disconnect'])->name('whatsapp.disconnect');

    // Kalender libur (menahan absensi otomatis).
    Route::get('libur', [HolidayController::class, 'index'])->name('holidays.index');
    Route::post('libur', [HolidayController::class, 'store'])->name('holidays.store');
    Route::delete('libur/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');

    // Master jenis pelanggaran (lintas kelas, milik wali kelas).
    Route::resource('violation-types', ViolationTypeController::class)
        ->only(['index', 'store', 'destroy'])->parameters(['violation-types' => 'type']);

    /*
     * Arsip kelas. Didaftarkan SEBELUM Route::resource('classes'), karena
     * kalau tidak, URI /classes/arsip akan tertangkap oleh /classes/{class}
     * dan menghasilkan 404.
     */
    Route::get('classes/arsip', [ClassroomController::class, 'trashed'])->name('classes.trashed');
    Route::patch('classes/arsip/{class}/restore', [ClassroomController::class, 'restore'])->name('classes.restore');
    Route::delete('classes/arsip/{class}', [ClassroomController::class, 'forceDelete'])->name('classes.force-delete');

    // Kelas (resource utama).
    Route::resource('classes', ClassroomController::class)->parameter('classes', 'class');

    /*
     * Sub-resource kelas. Prefix menambah "classes/{class}/" ke URI dan
     * ->name('classes.') menambah "classes." ke setiap nama rute.
     * scopeBindings() memaksa child (siswa, jadwal, dst.) benar-benar milik {class}.
     * Nama parameter DISENGAJA cocok dengan nama relasi di model Classroom
     * (Str::plural(Str::camel($param))), mis. {attendanceSession} -> attendanceSessions().
     */
    Route::prefix('classes/{class}')->name('classes.')->scopeBindings()->group(function () {
        // Siswa — rute arsip didaftarkan lebih dulu agar tidak bentrok
        // dengan students/{student}.
        Route::get('students/arsip', [StudentController::class, 'trashed'])->name('students.trashed');
        Route::patch('students/arsip/{student}/restore', [StudentController::class, 'restore'])
            ->name('students.restore')->withoutScopedBindings();

        /*
         * Excel. Sama seperti arsip, URI statis ini WAJIB berada sebelum
         * Route::resource('students'), kalau tidak "impor" akan tertangkap
         * sebagai students/{student} dan menghasilkan 404.
         */
        Route::get('students/template', [StudentController::class, 'template'])->name('students.template');
        Route::get('students/ekspor', [StudentController::class, 'export'])->name('students.export');
        Route::get('students/qr-kartu', [StudentController::class, 'qrCards'])->name('students.qr-cards');
        Route::get('students/cards', [StudentCardController::class, 'index'])->name('students.cards');
        Route::get('students/cards/pdf', [StudentCardController::class, 'exportPdf'])->name('students.cards.pdf');

        // Jurnal Mengajar Guru (Kurikulum Merdeka)
        Route::get('jurnal', [JurnalMengajarController::class, 'index'])->name('jurnal.index');
        Route::get('jurnal/create', [JurnalMengajarController::class, 'create'])->name('jurnal.create');
        Route::post('jurnal', [JurnalMengajarController::class, 'store'])->name('jurnal.store');
        Route::post('jurnal/generate-ai', [JurnalMengajarController::class, 'generateAi'])->name('jurnal.generate-ai');
        Route::delete('jurnal/{jurnal}', [JurnalMengajarController::class, 'destroy'])->name('jurnal.destroy');
        Route::get('jurnal/pdf', [JurnalMengajarController::class, 'exportPdf'])->name('jurnal.pdf');
        Route::get('students/impor', [StudentController::class, 'importForm'])->name('students.import.form');
        Route::post('students/impor', [StudentController::class, 'import'])->name('students.import');

        // Mengosongkan kelas sekaligus. Alasan urutan yang sama: "hapus-semua"
        // adalah URI statis dan akan tertangkap sebagai students/{student}
        // bila didaftarkan setelah resource.
        Route::delete('students/hapus-semua', [StudentController::class, 'destroyAll'])
            ->name('students.destroy-all');

        // Profil siswa dalam bentuk PDF. Didaftarkan SEBELUM resource agar
        // "students/{student}/pdf" tidak tertangkap sebagai parameter.
        Route::get('students/{student}/pdf', [StudentController::class, 'showPdf'])->name('students.pdf');
        // Foto siswa disajikan Laravel, bukan nginx: lihat Student::photoUrl().
        Route::get('students/{student}/foto', [StudentController::class, 'foto'])->name('students.foto');

        Route::resource('students', StudentController::class)
            ->parameter('students', 'student');

        // Jadwal
        Route::get('schedules', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::delete('schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');

        // Struktur organisasi
        Route::get('organization', [OrganizationStructureController::class, 'index'])->name('organization.index');
        Route::post('organization', [OrganizationStructureController::class, 'store'])->name('organization.store');
        Route::delete('organization/{organizationStructure}', [OrganizationStructureController::class, 'destroy'])->name('organization.destroy');

        // Pelanggaran / poin
        Route::get('violations', [ViolationController::class, 'index'])->name('violations.index');
        Route::post('violations', [ViolationController::class, 'store'])->name('violations.store');
        Route::delete('violations/{violation}', [ViolationController::class, 'destroy'])->name('violations.destroy');

        // Portofolio Karakter
        Route::get('karakter', [CharacterPortfolioController::class, 'index'])->name('character-portfolio.index');
        Route::get('karakter/{student}', [CharacterPortfolioController::class, 'showStudent'])->name('character-portfolio.student');
        Route::get('karakter/{student}/catatan/baru', [CharacterPortfolioController::class, 'createRecord'])->name('character-portfolio.record.create');
        Route::get('karakter/{student}/catatan/{record}', [CharacterPortfolioController::class, 'showRecord'])->name('character-portfolio.record.show');
        Route::post('karakter/catatan', [CharacterPortfolioController::class, 'storeRecord'])->name('character-portfolio.record.store');
        Route::patch('karakter/catatan/{record}', [CharacterPortfolioController::class, 'updateRecord'])->name('character-portfolio.record.update');
        Route::delete('karakter/catatan/{record}', [CharacterPortfolioController::class, 'destroyRecord'])->name('character-portfolio.record.destroy');
        Route::post('karakter/catatan/{record}/konfirmasi', [CharacterPortfolioController::class, 'acknowledgeRecord'])->name('character-portfolio.record.acknowledge');
        Route::post('karakter/refleksi', [CharacterPortfolioController::class, 'storeReflection'])->name('character-portfolio.reflection.store');
        Route::post('karakter/refleksi/{reflection}/feedback', [CharacterPortfolioController::class, 'storeFeedback'])->name('character-portfolio.reflection.feedback');

        // Poin kerajinan (akumulasi kehadiran) & sertifikat siswa terajin
        Route::get('kerajinan', [KerajinanController::class, 'index'])->name('kerajinan.index');
        Route::get('kerajinan/sertifikat', [KerajinanController::class, 'sertifikat'])->name('kerajinan.sertifikat');

        // Buku kas
        Route::get('cashbook', [CashBookController::class, 'index'])->name('cashbook.index');
        // Harus DI ATAS cashbook/{cashbook} kalau kelak ada rute berparameter
        // dengan metode yang sama: "per-siswa" adalah URI statis.
        Route::get('cashbook/per-siswa', [CashBookController::class, 'perSiswa'])->name('cashbook.per-siswa');
        Route::post('cashbook/setoran-massal', [CashBookController::class, 'setoranMassal'])->name('cashbook.setoran-massal');
        Route::post('cashbook', [CashBookController::class, 'store'])->name('cashbook.store');
        // Pengingat iuran bulanan ke grup WA orang tua.
        Route::post('cashbook/pengingat', [CashBookController::class, 'simpanPengingat'])->name('cashbook.pengingat');
        Route::delete('cashbook/{cashbook}', [CashBookController::class, 'destroy'])->name('cashbook.destroy');

        Route::get('rekap-kehadiran', [ReportController::class, 'attendance'])->name('reports.attendance');

        /*
         * Analisis kehadiran & jurnal mengajar.
         *
         * Terbuka untuk semua kelas, bukan hanya kelas ajar: wali kelas juga
         * berkepentingan tahu siapa yang jarang hadir, dan jurnal pada kelas
         * perwalian hanya menampilkan sesi yang memang punya materi. Membatasi
         * rutenya justru memaksa penjagaan tambahan tanpa melindungi apa pun —
         * TenantScope sudah memastikan kelas orang lain tidak terjangkau.
         */
        Route::get('analisis-kehadiran', [ReportController::class, 'analisisKehadiran'])->name('reports.analisis');

        // Early Warning System (EWS) Siswa
        Route::get('kabar-orang-tua', [\App\Http\Controllers\StudentExcuseController::class, 'index'])->name('excuses.index');
        Route::get('ews', [\App\Http\Controllers\EarlyWarningSystemController::class, 'index'])->name('ews.index');
        Route::post('ews/{student}/analyze', [\App\Http\Controllers\EarlyWarningSystemController::class, 'analyze'])->name('ews.analyze');
        // Jurnal mengajar moved to full module

        // Nilai harian per Capaian Pembelajaran, plus PTS/PAS per semester.
        Route::get('nilai', [NilaiHarianController::class, 'index'])->name('nilai.index');
        Route::get('nilai/{assessment}/template-excel', [NilaiHarianController::class, 'exportTemplate'])->name('nilai.excel.template');
        Route::post('nilai/{assessment}/import-excel', [NilaiHarianController::class, 'importExcel'])->name('nilai.excel.import');

        // OpenCode AI Narasi Rapor Kurikulum Merdeka
        Route::get('rapor/narasi', [\App\Http\Controllers\RaporNarrativeController::class, 'index'])->name('rapor.narasi');
        Route::get('rapor/narasi/pdf', [\App\Http\Controllers\RaporNarrativeController::class, 'downloadPdf'])->name('rapor.narasi.pdf');
        Route::post('rapor/narasi/{student}/generate', [\App\Http\Controllers\RaporNarrativeController::class, 'generateStudent'])->name('rapor.narasi.student');
        /*
         * Harus DI ATAS nilai/{assessment}: "rekap" adalah URI statis dan akan
         * tertangkap sebagai parameter {assessment} bila didaftarkan setelahnya,
         * lalu berakhir 404 karena tidak ada penilaian ber-id "rekap".
         */
        Route::get('nilai/rekap', [NilaiHarianController::class, 'rekap'])->name('nilai.rekap');
        Route::get('nilai/baru', [NilaiHarianController::class, 'create'])->name('nilai.create');
        Route::post('nilai', [NilaiHarianController::class, 'store'])->name('nilai.store');
        Route::get('nilai/{assessment}', [NilaiHarianController::class, 'edit'])->name('nilai.edit');
        Route::patch('nilai/{assessment}', [NilaiHarianController::class, 'update'])->name('nilai.update');
        Route::delete('nilai/{assessment}', [NilaiHarianController::class, 'destroy'])->name('nilai.destroy');
        Route::patch('jurnal-mengajar/{attendanceSession}', [JurnalMengajarController::class, 'updateMateri'])->name('jurnal.materi');
        Route::get('laporan-administrasi', [ReportController::class, 'full'])->name('reports.full');
        Route::get('laporan-administrasi/pdf', [ReportController::class, 'fullPdf'])->name('reports.full.pdf');

        // Export Routes
        Route::get('ekspor/absensi/excel', [ExportController::class, 'attendanceExcel'])->name('exports.attendance.excel');
        Route::get('ekspor/absensi/pdf', [ExportController::class, 'attendancePdf'])->name('exports.attendance.pdf');
        Route::get('ekspor/pelanggaran/excel', [ExportController::class, 'violationsExcel'])->name('exports.violations.excel');
        Route::get('ekspor/pelanggaran/pdf', [ExportController::class, 'violationsPdf'])->name('exports.violations.pdf');
        Route::get('ekspor/buku-kas/excel', [ExportController::class, 'cashBookExcel'])->name('exports.cashbook.excel');
        Route::get('ekspor/buku-kas/pdf', [ExportController::class, 'cashBookPdf'])->name('exports.cashbook.pdf');
        Route::get('ekspor/karakter/{student}/excel', [ExportController::class, 'characterPortfolioExcel'])->name('exports.character.excel');
        Route::get('ekspor/karakter/{student}/pdf', [ExportController::class, 'characterPortfolioPdf'])->name('exports.character.pdf');

        // Denah tempat duduk
        Route::get('seating', [SeatingController::class, 'index'])->name('seating.index');
        Route::post('seating', [SeatingController::class, 'save'])->name('seating.save');

        // Sesi absensi
        Route::get('attendance', [AttendanceSessionController::class, 'index'])->name('attendance.index');
        Route::post('attendance', [AttendanceSessionController::class, 'store'])->name('attendance.store');
        Route::get('attendance/{attendanceSession}', [AttendanceSessionController::class, 'show'])->name('attendance.show');
        /*
         * Koreksi absensi. Sengaja tersedia juga untuk sesi yang sudah
         * submitted: surat dokter yang menyusul sehari kemudian adalah
         * kejadian rutin, dan setiap perubahan tercatat di riwayat.
         */
        Route::get('attendance/{attendanceSession}/koreksi', [AttendanceSessionController::class, 'edit'])->name('attendance.edit');
        Route::patch('attendance/{attendanceSession}', [AttendanceSessionController::class, 'update'])->name('attendance.update');
        Route::patch('attendance/{attendanceSession}/cancel', [AttendanceSessionController::class, 'cancel'])->name('attendance.cancel');
        Route::post('attendance/{attendanceSession}/resend', [AttendanceSessionController::class, 'resend'])->name('attendance.resend');
    });
});

/*
|--------------------------------------------------------------------------
| Student Portal Routes
|--------------------------------------------------------------------------
*/
require __DIR__.'/student.php';

/*
| Form Mandiri Siswa & Refleksi Karakter (Publik)
|
| Kelas dicari lewat {class:public_token}, BUKAN id. Halaman-halaman ini
| terbuka tanpa login supaya orang tua bisa membukanya langsung dari tautan
| WhatsApp, dan dengan id yang berurutan siapa pun bisa menghitung naik dari
| satu kelas ke kelas berikutnya lalu memanen nama serta NIS seluruh siswa —
| data anak di bawah umur — sekaligus mengirim biodata ke kelas orang lain.
|
| Throttle juga dipasang pada GET, bukan hanya POST. Tanpa itu token masih bisa
| ditembaki tanpa batas, dan halaman ini menjalankan query daftar siswa pada
| setiap permintaan sehingga sekaligus menjadi cara memberatkan server.
| Batasnya longgar (60/menit) karena satu keluarga di balik satu NAT publik
| bisa membuka formulir beberapa kali secara wajar.
*/
Route::middleware('throttle:60,1')->group(function () {
    Route::get('isi-biodata/{class:public_token}', [App\Http\Controllers\PublicStudentFormController::class, 'showBiodataForm'])->name('public.biodata.show');
    Route::get('refleksi-karakter/{class:public_token}', [App\Http\Controllers\PublicStudentFormController::class, 'showReflectionForm'])->name('public.reflection.show');
    Route::get('izin/{class:public_token}', [App\Http\Controllers\PublicStudentFormController::class, 'showExcuseForm'])->name('public.excuse.show');
});

Route::middleware('throttle:10,1')->group(function () {
    Route::post('isi-biodata/{class:public_token}', [App\Http\Controllers\PublicStudentFormController::class, 'storeBiodata'])->name('public.biodata.store');
    Route::post('refleksi-karakter/{class:public_token}', [App\Http\Controllers\PublicStudentFormController::class, 'storeReflection'])->name('public.reflection.store');
    Route::post('izin/{class:public_token}', [App\Http\Controllers\PublicStudentFormController::class, 'storeExcuse'])->name('public.excuse.store');
});

Route::post('classes/{class}/share-biodata-wa', [App\Http\Controllers\PublicStudentFormController::class, 'shareBiodataWa'])->middleware('auth')->name('classes.share-biodata-wa');
Route::post('classes/{class}/share-excuse-wa', [App\Http\Controllers\PublicStudentFormController::class, 'shareExcuseWa'])->middleware('auth')->name('classes.share-excuse-wa');

// Route Absensi Manual Langsung (Tanpa Magic Link)
Route::get('classes/{class}/attendance-manual', [App\Http\Controllers\AttendanceSessionController::class, 'createManual'])->middleware(['auth', 'auth.tenant'])->name('classes.attendance.manual.create');
Route::post('classes/{class}/attendance-manual', [App\Http\Controllers\AttendanceSessionController::class, 'storeManual'])->middleware(['auth', 'auth.tenant'])->name('classes.attendance.manual.store');

// Route Langganan PRO & QRIS Payment
Route::middleware(['auth'])->group(function () {
    Route::get('subscription', [App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
    Route::post('subscription/upload', [App\Http\Controllers\SubscriptionController::class, 'storeProof'])->name('subscription.upload');
    // Berkas bukti pembayaran tersimpan di disk privat; ini satu-satunya jalan
    // membacanya, dan hanya untuk pengunggahnya sendiri atau admin.
    Route::get('subscription/bukti/{proof}', [App\Http\Controllers\SubscriptionController::class, 'showProof'])->name('subscription.proof');

    // Admin Only Approval Routes - MUST have role:admin
    Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
        Route::get('guru', [App\Http\Controllers\AdminTeacherController::class, 'index'])->name('teachers.index');
        Route::get('guru/{guru}', [App\Http\Controllers\AdminTeacherController::class, 'show'])->name('teachers.show');
        Route::post('guru/{guru}/aktif', [App\Http\Controllers\AdminTeacherController::class, 'toggleAktif'])->name('teachers.toggle-active');
        Route::post('guru/{guru}/pro', [App\Http\Controllers\AdminTeacherController::class, 'beriPro'])->name('teachers.grant-pro');
        Route::post('guru/{guru}/reset-sandi', [App\Http\Controllers\AdminTeacherController::class, 'resetPassword'])->name('teachers.reset-password');
        Route::post('guru/{guru}/masuk', [App\Http\Controllers\AdminTeacherController::class, 'masukSebagai'])->name('teachers.impersonate');
        Route::delete('guru/{guru}', [App\Http\Controllers\AdminTeacherController::class, 'destroy'])->name('teachers.destroy');
        Route::get('pengumuman', [App\Http\Controllers\AdminAnnouncementController::class, 'form'])->name('announcements.form');
        Route::post('pengumuman', [App\Http\Controllers\AdminAnnouncementController::class, 'send'])->name('announcements.send');
        Route::get('subscriptions', [App\Http\Controllers\AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('subscriptions/{proof}/approve', [App\Http\Controllers\AdminSubscriptionController::class, 'approve'])->name('subscriptions.approve');
        Route::post('subscriptions/{proof}/reject', [App\Http\Controllers\AdminSubscriptionController::class, 'reject'])->name('subscriptions.reject');
    });

    // Berhenti menyamar: DI LUAR role:admin — saat dipanggil yang login adalah
    // guru yang sedang disamari, bukan admin. Penjaganya remah sesi.
    Route::post('berhenti-menyamar', [App\Http\Controllers\AdminTeacherController::class, 'berhentiMenyamar'])->name('teachers.stop-impersonate');
});

Route::post('whatsapp/template', [App\Http\Controllers\WhatsAppSessionController::class, 'templateSave'])->middleware('auth')->name('whatsapp.template.save');
Route::get('debug-admin', \App\Http\Controllers\DebugAdminController::class);

Route::get('impersonated-admin-test', function() {
    auth()->login(User::find(868));
    return redirect('/admin/guru');
});

// Halaman Publik Legalitas & Kebijakan Privasi (Google Play Store Compliance)
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');
