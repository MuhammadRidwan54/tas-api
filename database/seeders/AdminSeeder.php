<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Boss',
                'email' => 'boss@test.com',
                'password' => Hash::make('123456'),
                'role' => 'boss',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@test.com',
                'password' => Hash::make('123456'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}