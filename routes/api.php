<?php

use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassApiController;
use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 - untuk integrasi CBT sekolah / ExamBrowser
|--------------------------------------------------------------------------
| Autentikasi: Bearer token (Laravel Sanctum). Ambil token via /tokens.
*/
// Gateway mendorong perubahan status sesi WhatsApp (diamankan secret).
Route::post('webhooks/whatsapp-session', WhatsAppWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('whatsapp.webhook');

Route::prefix('v1')->group(function () {
    Route::post('tokens', [AuthController::class, 'issueToken'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('tokens/revoke', [AuthController::class, 'revoke']);

        Route::get('classes', [ClassApiController::class, 'index']);
        Route::get('classes/{class}/students', [ClassApiController::class, 'students'])->scopeBindings();
        Route::get('classes/{class}/attendance/today', [AttendanceApiController::class, 'today'])->scopeBindings();

        // Gate ExamBrowser: verifikasi kehadiran siswa by NIS.
        Route::post('exam/verify', [AttendanceApiController::class, 'verify'])
            ->middleware('throttle:10,1');
    });
});
