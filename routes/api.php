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

// Laporan kasir, keuangan, produk terlaris
Route::middleware('auth:sanctum')->group(function () {
    Route::get('laporan/kasir', [LaporanController::class, 'kasir']);
    Route::get('laporan/keuangan', [LaporanController::class, 'keuangan']);
    Route::get('laporan/produk-terlaris', [LaporanController::class, 'produkTerlaris']);
});

// Dashboard omzet & transaksi
Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'summary']);
});

// Pengaturan Toko (show & update)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('pengaturan-toko', [PengaturanTokoController::class, 'show']);
    Route::put('pengaturan-toko', [PengaturanTokoController::class, 'update']);
});

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Produk CRUD, search, filter & user CRUD
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('kategori-produk', KategoriProdukController::class);
    Route::apiResource('produk', ProdukController::class);
    Route::apiResource('user', UserController::class);
    // Kasir: create transaksi
    Route::post('transaksi', [TransaksiController::class, 'store']);
});
