<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KategoriProdukController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\PengaturanTokoController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\TokoUserController;


//____AUTH PUBLIC___________________________________________-
Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});


//____SUPER ADMIN ONLY___________________________________________
//kelola semua toko semua owner semua data
Route::middleware(['auth:sanctum', 'role:super_admin'])->group(function (){
    Route::apiResource('toko', TokoController::class);
    // Crud user untuk super admin bisa lihat semua user lintas toko
});

//____SUPER ADMIN + OWNER___________________________________________
// Kelola staf toko, user, laporan, dashboard, pengaturan
Route::middleware(['auth:sanctum', 'role:super_admin,owner'])->group(function () {
    Route::apiResource('user', UserController::class);
    Route::get('laporan/kasir', [LaporanController::class, 'kasir']);
    Route::get('laporan/keuangan', [LaporanController::class, 'keuangan']);
    Route::get('laporan/produk-terlaris', [LaporanController::class, 'produkTerlaris']);
    Route::get('dashboard', [DashboardController::class, 'summary']);
    Route::get('pengaturan-toko', [PengaturanTokoController::class, 'show']);
    Route::put('pengaturan-toko', [PengaturanTokoController::class, 'update']);

    // Kelola staf di toko (assign, lihat, update role, keluarkan)
    Route::get('toko/{toko}/staf', [TokoUserController::class, 'index']);
    Route::post('toko/{toko}/staf', [TokoUserController::class, 'store']);
    Route::put('toko/{toko}/staf/{user}', [TokoUserController::class, 'update']);
    Route::delete('toko/{toko}/staf/{user}', [TokoUserController::class, 'destroy']);
});


//____SUPER ADMIN + OWNER + ADMIN + KASIR___________________________________
//kelola produk dan kategori
Route::middleware(['auth:sanctum', 'role:super_admin,owner,admin,kasir'])->group(function (){
    Route::apiResource('kategori-produk', KategoriProdukController::class);
    Route::apiResource('produk', ProdukController::class);
});

//____SEMUA ROLE TERMASUK KASIR_____________________________________________
//Kasir bisa buat transaksi
Route::middleware(['auth:sanctum', 'role:super_admin,owner,admin,kasir'])->group(function () {
    Route::apiResource('transaksi', TransaksiController::class)->only(['index', 'show', 'store']);
});
// Route lama dihapus — sudah dipindah ke group middleware role di atas
