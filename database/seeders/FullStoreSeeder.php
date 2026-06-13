<?php

namespace Database\Seeders;

use App\Models\DetailTransaksi;
use App\Models\KategoriProduk;
use App\Models\PengaturanToko;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FullStoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Memulai seeding untuk toko baru, owner, admin, kasir, produk, dan transaksi...');
        $suffix = Str::lower(Str::random(4));

        // 1. Buat Owner baru
        $owner = User::factory()->create([
            'name' => "Owner",
            'email' => "owner",
            'username' => "owner",
            'password' => Hash::make('owner123'),
            'role' => 'owner',
            'is_active' => true,
        ]);
        $this->command->info("Owner baru dibuat: {$owner->name} (ID: {$owner->id})");

        // 2. Buat Toko baru untuk Owner ini
        $toko = Toko::factory()->create([
            'owner_id' => $owner->id,
            'nama' => 'Toko Sio',
            'alamat' => 'Jl. Contoh No. ' . rand(1, 100),
            'telepon' => '08' . rand(1000000000, 9999999999),
            'is_active' => true,
        ]);
        $this->command->info("Toko baru dibuat: {$toko->nama} (ID: {$toko->id}) untuk Owner {$owner->name}");

        // 3. Buat Pengaturan Toko untuk toko baru
        PengaturanToko::create([
            'toko_id' => $toko->id,
            'ppn' => 11, // Default PPN 11%
            'catatan' => 'Terima kasih telah berbelanja di ' . $toko->nama,
        ]);
        $this->command->info("Pengaturan Toko dibuat untuk {$toko->nama}");

        // 4. Buat Admin baru untuk Toko ini
        $admin = User::factory()->create([
            'name' => 'Admin Toko '.$toko->nama,
            'email' => "admin@example.com",
            'username' => "admin",
            'password' => Hash::make('admin123'),
            'role' => 'staff', // Role sistem adalah 'staff'
            'is_active' => true,
        ]);
        // Hubungkan admin ke toko dengan pivot role 'admin'
        $admin->tokoTugas()->attach($toko->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->command->info("Admin baru dibuat: {$admin->name} (ID: {$admin->id}) untuk Toko {$toko->nama}");

        // 5. Buat Kasir baru untuk Toko ini
        $kasir = User::factory()->create([
            'name' => 'Kasir Toko ' . $toko->nama,
            'email' => "kasir@example.com",
            'username' => "kasir",
            'password' => Hash::make('kasir123'),
            'role' => 'staff', // Role sistem adalah 'staff'
            'is_active' => true,
        ]);
        // Hubungkan kasir ke toko dengan pivot role 'kasir'
        $kasir->tokoTugas()->attach($toko->id, [
            'role' => 'kasir',
            'is_active' => true,
        ]);
        $this->command->info("Kasir baru dibuat: {$kasir->name} (ID: {$kasir->id}) untuk Toko {$toko->nama}");

        // 6. Buat KategoriProduk khusus untuk toko baru ini agar terisolasi per toko
        $kategori = KategoriProduk::create([
            'kategori' => 'Umum',
            'toko_id' => $toko->id,
        ]);
        $this->command->info("Kategori Produk dibuat: {$kategori->kategori} (ID: {$kategori->id}) untuk Toko {$toko->nama}");

        // 7. Buat beberapa Produk untuk Toko baru
        $produks = Produk::factory(5)->create([
            'toko_id' => $toko->id,
            'kategori_id' => $kategori->id,
            'stok' => rand(20, 50), // Stok awal
        ]);
        $this->command->info("5 Produk dummy dibuat untuk Toko {$toko->nama}");

        // 8. Buat beberapa transaksi untuk Toko dan Kasir baru
        $ppnPercent = $toko->pengaturanToko->ppn ?? 0;
        $numberOfTransactions = 10;

        for ($i = 0; $i < $numberOfTransactions; $i++) {
            DB::transaction(function () use ($toko, $kasir, $produks, $ppnPercent) {
                $transaksi = Transaksi::factory()->create([
                    'toko_id' => $toko->id,
                    'user_id' => $kasir->id,
                    'tanggal' => now()->subHours(rand(1, 72)), // Sebar waktu dalam 3 hari terakhir
                ]);

                $subtotal = 0;
                $itemsInTransaction = $produks->random(rand(1, min(3, $produks->count())));

                foreach ($itemsInTransaction as $produk) {
                    $qty = rand(1, 3);
                    if ($produk->stok < $qty) {
                        $qty = $produk->stok > 0 ? 1 : 0;
                    }
                    if ($qty === 0) continue;

                    $itemTotal = $produk->harga * $qty;

                    DetailTransaksi::create([
                        'transaksi_id' => $transaksi->id,
                        'produk_id' => $produk->id,
                        'jumlah' => $qty,
                        'harga_saat_transaksi' => $produk->harga,
                        'subtotal' => $itemTotal,
                    ]);

                    $produk->decrement('stok', $qty);
                    $subtotal += $itemTotal;
                }

                $totalPpn = (int) round($subtotal * $ppnPercent / 100);
                $grandTotal = $subtotal + $totalPpn;
                $bayar = (int) ceil($grandTotal / 5000) * 5000;

                $transaksi->update([
                    'subtotal' => $subtotal,
                    'total_ppn' => $totalPpn,
                    'grand_total' => $grandTotal,
                    'nominal_bayar' => $bayar,
                    'kembalian' => $bayar - $grandTotal,
                ]);
            });
        }

        $this->command->info("{$numberOfTransactions} Transaksi berhasil dibuat untuk Toko: {$toko->nama} dan Kasir: {$kasir->name}");
        $this->command->info("Seeding untuk toko baru, owner, admin, kasir, produk, dan transaksi selesai!");
    }
}
