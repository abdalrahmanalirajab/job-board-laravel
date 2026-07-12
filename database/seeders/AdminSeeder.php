<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Ahmed Mahmoud',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+20 100 000 0001',
            ]
        );
    }
}