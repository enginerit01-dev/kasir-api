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
        Schema::table('users', function (Blueprint $table){
            //hapus  foreign key di toko id
            $table->dropForeign(['toko_id']);
$table->dropColumn('toko_id');
            // $table->dropColumn('toko_id');

            //ubah enum role dan tambahkan super_admin dan owner dan juga hapus admin dan kasir
            $table->dropColumn('role');


        });
                    Schema::table('users', function (Blueprint $table){
                $table->enum('role', ['super_admin','owner','staff'])->default('staff')->after('is_active');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('role');
    });
    Schema::table('users', function (Blueprint $table) {
        $table->enum('role', ['admin', 'kasir'])->after('is_active');
        $table->foreignId('toko_id')->constrained('toko')->cascadeOnDelete();
    });
}
};
