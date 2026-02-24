<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Caja;

class EmpresaSucursalSeeder extends Seeder
{
    public function run(): void
    {
        // Crear Empresa
        $empresa = Empresa::create([
            'razon_social' => 'Mi Empresa S.A.S',
            'nit' => '900123456',
            'dv' => '327',
            'direccion' => 'Calle 100 #20-30',
            'telefono' => '3001234567',
            'email' => 'info@miempresa.com',
            'activa' => true,
            'municipio_id' => '4',
        ]);

        // Crear Sucursales
        $sucursalPrincipal = Sucursal::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'SUC-001',
            'nombre' => 'Sucursal Principal',
            'direccion' => 'Calle 100 #20-30',
            'telefono' => '3001234567',
            'email' => 'principal@miempresa.com',
            'ciudad' => 'Bogotá',
            'es_principal' => true,
            'activa' => true,
        ]);

        $sucursalNorte = Sucursal::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'SUC-002',
            'nombre' => 'Sucursal Norte',
            'direccion' => 'Calle 170 #50-20',
            'telefono' => '3009876543',
            'email' => 'norte@miempresa.com',
            'ciudad' => 'Bogotá',
            'es_principal' => false,
            'activa' => true,
        ]);

        $sucursalSur = Sucursal::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'SUC-003',
            'nombre' => 'Sucursal Sur',
            'direccion' => 'Calle 20 Sur #30-40',
            'telefono' => '3005551234',
            'email' => 'sur@miempresa.com',
            'ciudad' => 'Bogotá',
            'es_principal' => false,
            'activa' => true,
        ]);

        // Asignar sucursales a usuarios existentes
        User::where('email', 'admin@pos.com')->update([
            'sucursal_id' => $sucursalPrincipal->id
        ]);

        User::where('email', 'supervisor@pos.com')->update([
            'sucursal_id' => $sucursalPrincipal->id
        ]);

        User::where('email', 'cajero@pos.com')->update([
            'sucursal_id' => $sucursalPrincipal->id
        ]);

        // Asignar sucursales a cajas existentes
        Caja::query()->update([
            'sucursal_id' => $sucursalPrincipal->id
        ]);

        $this->command->info('✅ Empresa y sucursales creadas exitosamente');
        $this->command->info("   Empresa: {$empresa->razon_social}");
        $this->command->info("   Sucursales creadas: 3");
        $this->command->info("   Usuarios asignados a sucursal principal");
        $this->command->info("   Cajas asignadas a sucursal principal");
    }
}