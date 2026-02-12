<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')
                  ->constrained('cajas')
                  ->onDelete('restrict');
            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->onDelete('restrict');
            $table->timestamp('fecha_apertura');
            $table->timestamp('fecha_cierre')->nullable();
            $table->decimal('monto_apertura', 10, 2);
            $table->decimal('monto_cierre', 10, 2)->nullable();
            $table->decimal('monto_esperado', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();
            $table->text('observaciones_apertura')->nullable();
            $table->text('observaciones_cierre')->nullable();
            $table->enum('estado', ['abierto', 'cerrado'])
                  ->default('abierto');
            $table->timestamps();

            $table->index('usuario_id');
            $table->index('fecha_apertura');
            $table->index('estado');
            $table->index('caja_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos_caja');
    }
};