<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesYSucursalesFinalSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔄 Configurando sistema de roles multi-sucursal...');
        $this->command->info('');

        // Obtener o crear sucursales
        $sucursales = $this->crearSucursales();
        
        // Crear roles
        $roles = $this->crearRoles($sucursales);
        
        // Asignar sucursales a roles
        $this->asignarSucursalesARoles($roles, $sucursales);
        
        // Crear usuarios de ejemplo
        $this->crearUsuarios($roles);
        
        // Mostrar resumen
        $this->mostrarResumen();
    }

    private function crearSucursales(): array
    {
        $this->command->info('📍 Verificando sucursales...');
        
        $sucursalCentro = Sucursal::firstOrCreate(
            ['codigo' => 'SUC-001'],
            [
                'empresa_id' => 1,
                'nombre' => 'Sucursal Centro',
                'direccion' => 'Calle 1 #1-01',
                'ciudad' => 'Bogotá',
                'es_principal' => true,
                'activa' => true,
            ]
        );

        $sucursalNorte = Sucursal::firstOrCreate(
            ['codigo' => 'SUC-002'],
            [
                'empresa_id' => 1,
                'nombre' => 'Sucursal Norte',
                'direccion' => 'Calle 2 #2-02',
                'ciudad' => 'Bogotá',
                'es_principal' => false,
                'activa' => true,
            ]
        );

        $sucursalSur = Sucursal::firstOrCreate(
            ['codigo' => 'SUC-003'],
            [
                'empresa_id' => 1,
                'nombre' => 'Sucursal Sur',
                'direccion' => 'Calle 3 #3-03',
                'ciudad' => 'Bogotá',
                'es_principal' => false,
                'activa' => true,
            ]
        );

        $this->command->info("  ✅ Sucursal Centro (ID: {$sucursalCentro->id})");
        $this->command->info("  ✅ Sucursal Norte (ID: {$sucursalNorte->id})");
        $this->command->info("  ✅ Sucursal Sur (ID: {$sucursalSur->id})");
        $this->command->info('');

        return [
            'centro' => $sucursalCentro,
            'norte' => $sucursalNorte,
            'sur' => $sucursalSur,
        ];
    }

    private function crearRoles(array $sucursales): array
    {
        $this->command->info('👥 Creando roles...');

        $rolesConfig = [
            'admin' => [
                'nombre' => 'admin',
                'descripcion' => 'Administrador con acceso a todas las sucursales',
            ],
            'cajero_centro' => [
                'nombre' => 'cajero_centro',
                'descripcion' => 'Cajero de Sucursal Centro',
            ],
            'cajero_norte_sur' => [
                'nombre' => 'cajero_norte_sur',
                'descripcion' => 'Cajero de Sucursales Norte y Sur',
            ],
            'cajero_todas' => [
                'nombre' => 'cajero_todas',
                'descripcion' => 'Cajero con acceso a todas las sucursales',
            ],
            'supervisor_regional' => [
                'nombre' => 'supervisor_regional',
                'descripcion' => 'Supervisor de Sucursales Centro y Norte',
            ],
        ];

        $roles = [];

        foreach ($rolesConfig as $key => $config) {
            $rol = Rol::firstOrCreate(
                ['nombre' => $config['nombre']],
                [
                    'descripcion' => $config['descripcion'],
                    'activo' => true,
                ]
            );

            $roles[$key] = $rol;
            $this->command->info("  ✅ {$rol->nombre}");
        }

        $this->command->info('');
        return $roles;
    }

    private function asignarSucursalesARoles(array $roles, array $sucursales): void
    {
        $this->command->info('🔗 Asignando sucursales a roles...');

        // Admin: TODAS (esto es automático por el código, no necesita asignación)
        $this->command->info('  ✅ admin → TODAS (automático)');

        // Cajero Centro: Solo Centro
        $roles['cajero_centro']->sucursales()->sync([$sucursales['centro']->id]);
        $this->command->info('  ✅ cajero_centro → [Centro]');

        // Cajero Norte y Sur: Norte y Sur
        $roles['cajero_norte_sur']->sucursales()->sync([
            $sucursales['norte']->id,
            $sucursales['sur']->id,
        ]);
        $this->command->info('  ✅ cajero_norte_sur → [Norte, Sur]');

        // Cajero Todas: Centro, Norte y Sur
        $roles['cajero_todas']->sucursales()->sync([
            $sucursales['centro']->id,
            $sucursales['norte']->id,
            $sucursales['sur']->id,
        ]);
        $this->command->info('  ✅ cajero_todas → [Centro, Norte, Sur]');

        // Supervisor Regional: Centro y Norte
        $roles['supervisor_regional']->sucursales()->sync([
            $sucursales['centro']->id,
            $sucursales['norte']->id,
        ]);
        $this->command->info('  ✅ supervisor_regional → [Centro, Norte]');

        $this->command->info('');
    }

    private function crearUsuarios(array $roles): void
    {
        $this->command->info('👤 Creando usuarios de ejemplo...');

        $usuarios = [
            [
                'nombre' => 'Administrador Sistema',
                'email' => 'admin@pos.com',
                'password' => 'password',
                'rol' => 'admin',
            ],
            [
                'nombre' => 'Juan Cajero Centro',
                'email' => 'juan@pos.com',
                'password' => 'password',
                'rol' => 'cajero_centro',
            ],
            [
                'nombre' => 'María Cajero Todas',
                'email' => 'maria@pos.com',
                'password' => 'password',
                'rol' => 'cajero_todas',
            ],
            [
                'nombre' => 'Pedro Supervisor',
                'email' => 'pedro@pos.com',
                'password' => 'password',
                'rol' => 'supervisor_regional',
            ],
            [
                'nombre' => 'Ana Cajero Norte-Sur',
                'email' => 'ana@pos.com',
                'password' => 'password',
                'rol' => 'cajero_norte_sur',
            ],
        ];

        foreach ($usuarios as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'nombre' => $userData['nombre'],
                    'name' => $userData['nombre'],
                    'password' => Hash::make($userData['password']),
                    'rol_id' => $roles[$userData['rol']]->id,
                    'activo' => true,
                ]
            );

            // Actualizar rol_id si el usuario ya existía
            if ($user->wasRecentlyCreated === false) {
                $user->update(['rol_id' => $roles[$userData['rol']]->id]);
            }

            $this->command->info("  ✅ {$user->email} → {$userData['rol']}");
        }

        $this->command->info('');
    }

    private function mostrarResumen(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('                    RESUMEN FINAL                      ');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('');

        foreach (User::with('rol')->get() as $user) {
            $sucursales = $user->sucursalesAccesibles();
            $sucursalesNombres = $sucursales->pluck('nombre')->implode(', ');
            
            $this->command->info("👤 {$user->nombre}");
            $this->command->info("   Email: {$user->email}");
            $this->command->info("   Password: password");
            $this->command->info("   Rol: {$user->rol->nombre}");
            $this->command->info("   Sucursales: {$sucursalesNombres} ({$sucursales->count()})");
            $this->command->info('');
        }

        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('✅ Sistema configurado exitosamente');
        $this->command->info('');
    }
}