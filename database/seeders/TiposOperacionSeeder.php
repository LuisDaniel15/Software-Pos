<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposOperacionSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'codigo' => '10',
                'descripcion' => 'Estándar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '11',
                'descripcion' => 'Mandatos',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '12',
                'descripcion' => 'Transporte',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tipos_operacion')->insert($tipos);
    }
}