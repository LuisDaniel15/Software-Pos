<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormasPagoSeeder extends Seeder
{
    public function run(): void
    {
        $formas = [
            [
                'codigo' => '1',
                'nombre' => 'Pago de contado',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '2',
                'nombre' => 'Pago a crédito',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('formas_pago')->insert($formas);
    }
}