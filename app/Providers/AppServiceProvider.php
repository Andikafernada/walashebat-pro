<?php

namespace App\Providers;

use App\Models\CashBook;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Violation;
use App\Models\AttendanceSession;
use App\Policies\AttendanceSessionPolicy;
use App\Policies\CashBookPolicy;
use App\Policies\ClassroomPolicy;
use App\Policies\StudentPolicy;
use App\Policies\ViolationPolicy;
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
         * Daftarkan policies untuk authorization.
         * TenantScope menangani isolasi data di level query,
         * policies sebagai layer tambahan dan untuk multi-peran di masa depan.
         */
        Gate::policy(Classroom::class, ClassroomPolicy::class);
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(AttendanceSession::class, AttendanceSessionPolicy::class);
        Gate::policy(Violation::class, ViolationPolicy::class);
        Gate::policy(CashBook::class, CashBookPolicy::class);

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

        // Pintasan absensi di sidebar — hanya tampil untuk user yang sudah login.
        View::composer('layouts.app', function ($view) {
            $view->with('kelasPintasan', Auth::check()
                ? \App\Models\Classroom::where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                : collect());
        });
    }
}
