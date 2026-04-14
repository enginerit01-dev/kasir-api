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
        Schema::create('pengaturan_toko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toko_id')->unique()->constrained('toko')->cascadeOnDelete();
            $table->unsignedTinyInteger('ppn')->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_toko');
    }
};
