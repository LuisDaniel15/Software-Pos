<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')
                ->constrained('empresa')
                ->onDelete('cascade');
            $table->string('codigo', 20);
            $table->string('nombre', 100);
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->foreignId('municipio_id')
                ->nullable()
                ->constrained('municipios')
                ->onDelete('set null');
            $table->boolean('es_principal')->default(false);
            $table->boolean('activa')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->unique(['empresa_id', 'codigo']);
            $table->index('empresa_id');
            $table->index('activa');
            $table->index('es_principal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};