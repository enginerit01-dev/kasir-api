<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::firstOrCreate([
            'email' => 'superadmin@gmail.com'
        ], [
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'password' => \Illuminate\Support\Facades\Hash::make('superadmin26'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }
}
