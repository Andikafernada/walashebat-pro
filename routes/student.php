<?php

use App\Http\Controllers\Student\AuthController;
use App\Http\Controllers\Student\BiodataController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\PortfolioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Student Portal Routes
|--------------------------------------------------------------------------
|
| Siswa login dengan NIS dan password. Semua route dilindungi middleware 'student.auth'.
| Siswa hanya bisa akses data DIRINYA sendiri.
|
*/

// Guest routes (belum login)
Route::middleware('guest:student')->group(function () {
    Route::get('student/login', [AuthController::class, 'showLogin'])->name('student.login');

    /*
     * Seluruh titik masuk kredensial siswa dibatasi lajunya.
     *
     * Password siswa diterbitkan wali kelas dan sering pendek atau seragam
     * antar siswa, sementara NIS bersifat berurutan dan mudah ditebak — tanpa
     * batas percobaan, menebak satu akun kelas hanya soal waktu. Kode OTP 6
     * digit lebih rapuh lagi: 1 juta kemungkinan habis dalam hitungan menit
     * bila boleh dicoba tanpa henti.
     */
    Route::post('student/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::get('student/lupa-password', [AuthController::class, 'showForgotPassword'])->name('student.password.request');
    Route::post('student/lupa-password', [AuthController::class, 'sendOtp'])
        ->middleware('throttle:5,1')->name('student.password.email');
    Route::get('student/otp/{nis}', [AuthController::class, 'verifyOtp'])->name('student.otp.verify');
    Route::post('student/otp/{nis}', [AuthController::class, 'processOtp'])
        ->middleware('throttle:10,1')->name('student.otp.submit');
});

// Protected routes (harus login sebagai siswa)
Route::middleware('student.auth')->group(function () {
    // Logout
    Route::post('student/logout', [AuthController::class, 'logout'])->name('student.logout');
    Route::get('student/logout', fn () => redirect()->route('student.login'));

    /*
     * Formulir ganti kata sandi. TIDAK dibungkus middleware wajib-ganti —
     * justru kedua rute inilah yang dikecualikan di dalam middleware itu.
     *
     * Dulu grup middleware-nya dipasang tepat di sekitar dua rute ini saja,
     * sehingga ia membungkus persis rute yang selalu ia loloskan: gerbangnya
     * tidak pernah menyala di mana pun, dan seluruh halaman di bawah — dashboard,
     * biodata (alamat, nama & nomor HP orang tua), portofolio — terbuka bagi
     * siapa pun yang berhasil masuk dengan kata sandi awal, tanpa pernah
     * dipaksa menggantinya.
     */
    Route::get('student/password/ubah', [AuthController::class, 'showPasswordChange'])->name('student.password.change');
    // WAJIB bernama: StudentMustChangePassword meloloskan request berdasarkan
    // NAMA route. Selama POST ini anonim, pengirimannya ikut dialihkan balik ke
    // formulir dan siswa terkunci dalam loop tanpa ujung.
    Route::post('student/password/ubah', [AuthController::class, 'changePassword'])
        ->name('student.password.update');

    // Seluruh halaman berisi data pribadi ada DI DALAM gerbang ini.
    Route::middleware('student.must.change.password')->group(function () {

    // Dashboard
    Route::get('student/dashboard', [DashboardController::class, 'index'])->name('student.dashboard');

    // Biodata
    Route::get('student/biodata', [BiodataController::class, 'show'])->name('student.biodata');
    Route::get('student/biodata/edit', [BiodataController::class, 'edit'])->name('student.biodata.edit');
    Route::match(['put', 'patch'], 'student/biodata', [BiodataController::class, 'update'])->name('student.biodata.update');
    Route::get('student/biodata/password', [BiodataController::class, 'showPassword'])->name('student.biodata.password');
    Route::match(['put', 'patch'], 'student/biodata/password', [BiodataController::class, 'updatePassword'])->name('student.biodata.password.update');

    // Portfolio
    Route::get('student/portofolio', [PortfolioController::class, 'index'])->name('student.portfolio');
    Route::get('student/portofolio/{dimension}', [PortfolioController::class, 'dimension'])->name('student.portfolio.dimension');

    // Student can add their own records
    Route::post('student/portofolio/pencapaian', [PortfolioController::class, 'storeAchievement'])->name('student.portfolio.achievement');
    Route::post('student/portofolio/catatan', [PortfolioController::class, 'storeObservation'])->name('student.portfolio.observation');

    // Reflections
    Route::get('student/refleksi/baru', [PortfolioController::class, 'createReflection'])->name('student.reflection.create');
    Route::post('student/refleksi', [PortfolioController::class, 'storeReflection'])->name('student.reflection.store');
    }); // tutup gerbang wajib-ganti-kata-sandi
});
