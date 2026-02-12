<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposDocumentoIdentidadSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['id' => 1, 'nombre' => 'Registro civil', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'nombre' => 'Tarjeta de identidad', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'nombre' => 'Cédula de ciudadanía', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'nombre' => 'Tarjeta de extranjería', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'nombre' => 'Cédula de extranjería', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'nombre' => 'NIT', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'nombre' => 'Pasaporte', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'nombre' => 'Documento de identificación extranjero', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'nombre' => 'PEP', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'nombre' => 'NIT otro país', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'nombre' => 'NUIP', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('tipos_documento_identidad')->insert($tipos);
    }
}