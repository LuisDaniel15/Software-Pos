<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1️⃣ Crear tabla roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 2️⃣ Crear tabla pivot rol_sucursal
        Schema::create('rol_sucursal', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rol_id')
                ->constrained('roles')
                ->onDelete('cascade');

            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->onDelete('cascade');

            $table->timestamps();

            // Evita duplicados: mismo rol en misma sucursal
            $table->unique(['rol_id', 'sucursal_id']);

            // Índices para rendimiento
            $table->index('rol_id');
            $table->index('sucursal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_sucursal');
        Schema::dropIfExists('roles');
    }
};