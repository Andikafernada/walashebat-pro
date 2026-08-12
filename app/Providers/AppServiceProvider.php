<?php

namespace App\Providers;

use App\Models\CashBook;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Violation;
use App\Models\AttendanceSession;
use App\Support\Contracts\NotificationChannel;
use App\Support\Contracts\WhatsAppSessionManager;
use App\Support\Notifications\LogChannel;
use App\Support\Notifications\N8nSessionManager;
use App\Support\Notifications\N8nWhatsAppChannel;
use App\Support\Notifications\NullSessionManager;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Pilih channel notifikasi berdasarkan konfigurasi.
        $this->app->bind(NotificationChannel::class, function () {
            $driver = config('walikelas.whatsapp.driver', 'log');

            if ($driver === 'n8n' && config('walikelas.whatsapp.n8n.webhook_url')) {
                return new N8nWhatsAppChannel(
                    webhookUrl: config('walikelas.whatsapp.n8n.webhook_url'),
                    secret: config('walikelas.whatsapp.n8n.secret'),
                    timeout: (int) config('walikelas.whatsapp.n8n.timeout', 8),
                );
            }

            return new LogChannel;
        });

        // Pengelola sesi WhatsApp per guru (satu nomor per wali kelas).
        $this->app->bind(WhatsAppSessionManager::class, function () {
            $url = config('walikelas.whatsapp.n8n.session_url');

            if (config('walikelas.whatsapp.driver') === 'n8n' && $url) {
                return new N8nSessionManager(
                    webhookUrl: $url,
                    secret: config('walikelas.whatsapp.n8n.secret'),
                );
            }

            return new NullSessionManager;
        });
    }

    public function boot(): void
    {
        /*
         * Policy sengaja TIDAK ada lagi.
         *
         * Kelimanya terdaftar di sini selama berbulan-bulan tanpa satu pun
         * pemanggilan: nol authorize(), nol Gate::, nol @can di 38 controller
         * dan seluruh Blade. Isinya pun hampir seluruhnya
         * `$model->user_id === $user->id` — persis yang kini ditegakkan
         * TenantScope di lapisan query, gagal-tertutup dan berpagar test.
         *
         * Satu-satunya aturan yang benar-benar menambah — sesi absensi hanya
         * boleh dibatalkan selagi terbuka — dipindahkan ke
         * AttendanceSessionController::cancel(), tempat ia bisa berlaku.
         *
         * Kode mati yang menyerupai pengaman lebih berbahaya daripada tidak
         * ada pengaman: ia membuat pembaca berikutnya mengira otorisasi sudah
         * ditangani.
         */

        /*
         * Di balik Cloudflare Tunnel, permintaan tiba di nginx sebagai HTTP
         * biasa. Tanpa ini Laravel membangun URL aset dan form dengan skema
         * http, lalu browser memblokirnya sebagai mixed content — halaman
         * tampil tanpa CSS sama sekali.
         */
        if (app()->environment('production') && ! app()->runningInConsole()) {
            URL::forceScheme('https');
        }

        // Kompatibilitas index key untuk MySQL versi lama.
        Schema::defaultStringLength(191);

        /*
         * Daftar kelas untuk tombol ganti kelas di chrome — hanya untuk user
         * yang sudah login. Sampai chrome memakai pembatas buku, hasil kueri
         * ini tidak dipakai satu view pun: ia berjalan di setiap permintaan
         * halaman lalu dibuang.
         */
        View::composer('layouts.app', function ($view) {
            $view->with('kelasPintasan', Auth::check()
                ? \App\Models\Classroom::where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : collect());
        });
    }
}
