<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_caja_id')
                  ->constrained('turnos_caja')
                  ->onDelete('cascade');
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->string('concepto', 255);
            $table->decimal('monto', 10, 2);
            $table->foreignId('metodo_pago_id')
                  ->nullable()
                  ->constrained('metodos_pago')
                  ->onDelete('set null');
            $table->unsignedBigInteger('venta_id')->nullable(); // ⚠️ Sin FK por ahora
            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->onDelete('restrict');
            $table->text('observacion')->nullable();
            $table->timestamp('created_at');

            $table->index('turno_caja_id');
            $table->index('tipo');
            $table->index('venta_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};