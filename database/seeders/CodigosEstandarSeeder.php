<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CodigosEstandarSeeder extends Seeder
{
    public function run(): void
    {
        $codigos = [
            [
                'id' => 1,
                'nombre' => 'Estándar de adopción del contribuyente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nombre' => 'UNSPSC',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'nombre' => 'Partida Arancelaria',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'nombre' => 'GTIN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('codigos_estandar')->insert($codigos);
    }
}