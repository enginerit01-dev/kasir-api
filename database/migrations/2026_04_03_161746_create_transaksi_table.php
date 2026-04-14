<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi', 50)->unique();
            $table->dateTime('tanggal');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total_ppn');
            $table->unsignedBigInteger('grand_total');
            $table->unsignedBigInteger('nominal_bayar');
            $table->enum('metode_pembayaran', ['cash', 'qris', 'debit', 'kredit']);
            $table->unsignedBigInteger('kembalian');
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('toko_id')->constrained('toko')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
