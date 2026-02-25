<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesYSucursalesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔄 Creando roles...');

        // Crear roles
        $admin = Rol::create([
            'nombre' => 'admin',
            'descripcion' => 'Administrador con acceso a todas las sucursales',
            'activo' => true,
        ]);

        $supervisor = Rol::create([
            'nombre' => 'supervisor',
            'descripcion' => 'Supervisa sucursales asignadas',
            'activo' => true,
        ]);

        $cajero = Rol::create([
            'nombre' => 'cajero',
            'descripcion' => 'Cajero asignado a sucursales específicas',
            'activo' => true,
        ]);

        $this->command->info('✅ Roles creados');

        // Obtener sucursales
        $sucursales = Sucursal::all();

        if ($sucursales->isEmpty()) {
            $this->command->warn('⚠️  No hay sucursales. Créalas primero.');
            return;
        }

        $this->command->info('🔄 Asignando sucursales a roles...');

        // Admin: todas las sucursales (esto es automático por el accessor)
        $this->command->info("  → Admin: Acceso a todas las sucursales (automático)");

        // Supervisor: centro y norte (primeras 2 sucursales)
        $sucursalesSupervisor = $sucursales->take(2);
        $supervisor->sucursales()->attach($sucursalesSupervisor->pluck('id'));
        $this->command->info("  → Supervisor: " . $sucursalesSupervisor->pluck('nombre')->implode(', '));

        // Cajero: solo una sucursal (última)
        $sucursalCajero = $sucursales->last();
        $cajero->sucursales()->attach($sucursalCajero->id);
        $this->command->info("  → Cajero: {$sucursalCajero->nombre}");

        $this->command->info('✅ Sucursales asignadas a roles');

        // Actualizar usuarios existentes
        $this->command->info('🔄 Actualizando usuarios...');

        $userAdmin = User::where('email', 'admin@pos.com')->first();
        if ($userAdmin) {
            $userAdmin->update(['rol_id' => $admin->id]);
            $this->command->info("  → {$userAdmin->email} → Admin");
        }

        $userSupervisor = User::where('email', 'supervisor@pos.com')->first();
        if ($userSupervisor) {
            $userSupervisor->update(['rol_id' => $supervisor->id]);
            $this->command->info("  → {$userSupervisor->email} → Supervisor");
        }

        $userCajero = User::where('email', 'cajero@pos.com')->first();
        if ($userCajero) {
            $userCajero->update(['rol_id' => $cajero->id]);
            $this->command->info("  → {$userCajero->email} → Cajero");
        }

        $this->command->info('✅ Usuarios actualizados');
        $this->command->info('');
        $this->command->info('📊 Resumen:');
        $this->command->info("   • Roles: {$admin->nombre}, {$supervisor->nombre}, {$cajero->nombre}");
        $this->command->info("   • Admin: Acceso a todas las sucursales");
        $this->command->info("   • Supervisor: {$sucursalesSupervisor->count()} sucursales");
        $this->command->info("   • Cajero: 1 sucursal");
    }
}