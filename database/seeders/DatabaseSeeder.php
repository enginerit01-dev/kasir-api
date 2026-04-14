<?php

namespace Database\Seeders;

use App\Models\Toko;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
      Toko::factory()->create([
        "nama" => "HMTI store",
          "alamat" => "jalan jati metro",
          "telepon" => "081287790774",
          "is_active" => true,
      ]);
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Nabil Tamim Abdullah',
            'email' => 'nabil@gmail.com',
            'username' => 'nabil',
            'password'=> '12345678',
            'is_active' => true,
            'role' => 'admin',
            'toko_id' => 1,
        ]);

    }
}
