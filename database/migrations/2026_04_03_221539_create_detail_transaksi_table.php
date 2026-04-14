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
        Schema::create('detail_transaksi', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('jumlah');
            $table->unsignedBigInteger('harga_saat_transaksi');
            $table->unsignedBigInteger('subtotal');
            $table->foreignId('transaksi_id')->constrained('transaksi')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->unique(['transaksi_id', 'produk_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_transaksi');
    }
};
