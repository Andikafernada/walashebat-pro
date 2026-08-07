<?php

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Buku Kas kelas: pemasukan/pengeluaran dengan saldo berjalan. */
    public function up(): void
    {
        Schema::create('cash_books', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Classroom::class, 'class_id')->constrained('classes')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->enum('type', ['in', 'out']);
            $table->unsignedBigInteger('amount'); // rupiah, tanpa desimal
            $table->string('description');
            $table->unsignedBigInteger('balance_after')->default(0); // saldo setelah transaksi
            $table->timestamps();

            $table->index(['user_id', 'class_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_books');
    }
};
