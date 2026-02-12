<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'nombre' => 'Administrador',
                'name' => 'Admin',
                'email' => 'admin@pos.com',
                'password' => Hash::make('2025admin**'),
                'rol' => 'admin',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Cajero Demo',
                'name' => 'Cajero',
                'email' => 'cajero@pos.com',
                'password' => Hash::make('2025caja**'),
                'rol' => 'cajero',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Supervisor Demo',
                'name' => 'Supervisor',
                'email' => 'supervisor@pos.com',
                'password' => Hash::make('2025super**'),
                'rol' => 'supervisor',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);
    }
}