<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TributosClienteSeeder extends Seeder
{
    public function run(): void
    {
        $tributos = [
            [
                'id' => 18,
                'codigo' => '01',
                'nombre' => 'IVA',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 21,
                'codigo' => 'ZZ',
                'nombre' => 'No aplica',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tributos_cliente')->insert($tributos);
    }
}