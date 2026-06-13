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
        Schema::create('produk', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nama', 100);
            $table->string('gambar')->nullable();
            $table->unsignedBigInteger('harga');
            $table->unsignedInteger('stok');
            $table->foreignUlid('kategori_id')->constrained('kategori_produk')->restrictOnDelete();
            $table->foreignUlid('toko_id')->nullable()->constrained('toko')->restrictOnDelete();
            $table->string('kode_produk',50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
