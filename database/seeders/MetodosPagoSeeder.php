<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MetodosPagoSeeder extends Seeder
{
    public function run(): void
    {
        $metodos = [
            ['codigo' => '1', 'nombre' => 'Medio de pago no definido', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '10', 'nombre' => 'Efectivo', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '20', 'nombre' => 'Cheque', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '42', 'nombre' => 'Consignación', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '47', 'nombre' => 'Transferencia', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '48', 'nombre' => 'Tarjeta Crédito', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '49', 'nombre' => 'Tarjeta Débito', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '71', 'nombre' => 'Bonos', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => '72', 'nombre' => 'Vales', 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'ZZZ', 'nombre' => 'Otro', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('metodos_pago')->insert($metodos);
    }
}