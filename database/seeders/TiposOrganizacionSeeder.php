<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposOrganizacionSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'id' => 1,
                'nombre' => 'Persona Jurídica',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nombre' => 'Persona Natural',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tipos_organizacion')->insert($tipos);
    }
}