<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DailyDatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:database-daily {--keep=7 : Jumlah hari retensi berkas backup}';

    /**
     * The console command description.
     */
    protected $description = 'Melakukan backup database terenkripsi & terkompresi harian secara otomatis.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Memulai backup database otomatis...');

        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        if (empty($dbName) || empty($dbUser)) {
            $this->error('❌ Konfigurasi database tidak lengkap.');
            return self::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0750, true, true);
        }

        $timestamp = now()->format('Y-m-d_His');
        $filename = "walikelas_backup_{$dbName}_{$timestamp}.sql.gz";
        $targetPath = "{$backupDir}/{$filename}";

        // Jalankan mysqldump langsung dikompresi dengan gzip
        $cmd = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --quick --routines %s 2>/dev/null | gzip -9 > %s',
            escapeshellarg($dbHost),
            escapeshellarg($dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbPass),
            escapeshellarg($dbName),
            escapeshellarg($targetPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0 || !File::exists($targetPath) || File::size($targetPath) === 0) {
            $this->error("❌ Gagal mengekspor database. Return code: {$returnCode}");
            Log::error("[BACKUP] Gagal membuat backup database {$dbName}");
            return self::FAILURE;
        }

        $sizeMb = round(File::size($targetPath) / (1024 * 1024), 2);
        $this->info("✅ Backup berhasil disimpan: {$filename} ({$sizeMb} MB)");
        Log::info("[BACKUP] Database {$dbName} berhasil dibackup: {$filename} ({$sizeMb} MB)");

        // 1. Upload ke S3 / Cloudflare R2 jika disk 's3' terkonfigurasi
        try {
            if (config('filesystems.disks.s3.key') && config('filesystems.disks.s3.bucket')) {
                Storage::disk('s3')->put("database-backups/{$filename}", fopen($targetPath, 'r+'));
                $this->info("☁️ Berhasil diunggah ke Cloud Storage (S3/R2).");
            }
        } catch (\Throwable $e) {
            Log::warning("[BACKUP] Gagal upload ke S3/R2: " . $e->getMessage());
        }

        // 2. Pembersihan otomatis berkas lama (Retention Auto-Pruning)
        $keepDays = (int) $this->option('keep');
        $this->pruneOldBackups($backupDir, $keepDays);

        return self::SUCCESS;
    }

    /**
     * Hapus berkas backup lokal yang lebih tua dari batas retensi.
     */
    private function pruneOldBackups(string $backupDir, int $keepDays): void
    {
        $cutoffTime = now()->subDays($keepDays)->timestamp;
        $files = File::glob("{$backupDir}/*.sql.gz");
        $deletedCount = 0;

        foreach ($files as $file) {
            if (File::lastModified($file) < $cutoffTime) {
                File::delete($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("🧹 Menghapus {$deletedCount} berkas backup lama (retensi > {$keepDays} hari).");
        }
    }
}
