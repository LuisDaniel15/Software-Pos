<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposDocumentoFacturaSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'codigo' => '01',
                'descripcion' => 'Factura electrónica de venta',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '03',
                'descripcion' => 'Instrumento electrónico de transmisión - tipo 03',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tipos_documento_factura')->insert($tipos);
    }
}