<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->dropUnique('kategori_produk_kategori_unique');
            $table->foreignUlid('toko_id')->nullable()->constrained('toko')->cascadeOnDelete();
        });

        // Set default toko for existing categories
        $firstToko = DB::table('toko')->first();
        if ($firstToko) {
            DB::table('kategori_produk')->whereNull('toko_id')->update(['toko_id' => $firstToko->id]);
        }

        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->unique(['toko_id', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::table('kategori_produk', function (Blueprint $table) {
            $table->dropUnique(['toko_id', 'kategori']);
            $table->dropConstrainedForeignId('toko_id');
            $table->unique('kategori');
        });
    }
};
