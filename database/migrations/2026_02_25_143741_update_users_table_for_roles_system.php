<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Agregar rol_id
            $table->foreignId('rol_id')
                ->nullable()
                ->after('password')
                ->constrained('roles')
                ->onDelete('set null');
            
            $table->index('rol_id');
        });

        // 2. Migrar datos del campo 'rol' al nuevo sistema
        $this->migrarRoles();

        // 3. Eliminar campo 'rol' string y 'sucursal_id'
        Schema::table('users', function (Blueprint $table) {
            // Eliminar sucursal_id (ahora viene de rol_sucursal)
            if (Schema::hasColumn('users', 'sucursal_id')) {
                $table->dropForeign(['sucursal_id']);
                $table->dropColumn('sucursal_id');
            }
            
            // Eliminar campo rol string
            if (Schema::hasColumn('users', 'rol')) {
                $table->dropColumn('rol');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restaurar campo rol string
            $table->enum('rol', ['admin', 'supervisor', 'cajero'])->default('cajero');
            
            // Restaurar sucursal_id
            $table->foreignId('sucursal_id')
                ->nullable()
                ->constrained('sucursales')
                ->onDelete('set null');
            
            // Eliminar rol_id
            $table->dropForeign(['rol_id']);
            $table->dropColumn('rol_id');
        });
    }

    /**
     * Migrar usuarios del sistema antiguo al nuevo
     */
    private function migrarRoles(): void
    {
        // Obtener roles del nuevo sistema
        $roles = DB::table('roles')->get()->keyBy('nombre');

        if ($roles->isEmpty()) {
            return;
        }

        // Migrar cada usuario
        $usuarios = DB::table('users')->get();

        foreach ($usuarios as $usuario) {
            $rolNombre = $usuario->rol ?? 'cajero';
            
            if (isset($roles[$rolNombre])) {
                DB::table('users')
                    ->where('id', $usuario->id)
                    ->update(['rol_id' => $roles[$rolNombre]->id]);
            }
        }
    }
};