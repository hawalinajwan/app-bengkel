<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Admin Bengkel', 'email' => 'admin@bengkel.test', 'password' => 'password', 'role' => 'admin']);
        User::create(['name' => 'Kasir Satu', 'email' => 'kasir1@bengkel.test', 'password' => 'password', 'role' => 'kasir']);
        User::create(['name' => 'Kasir Dua', 'email' => 'kasir2@bengkel.test', 'password' => 'password', 'role' => 'kasir']);
    }
}
