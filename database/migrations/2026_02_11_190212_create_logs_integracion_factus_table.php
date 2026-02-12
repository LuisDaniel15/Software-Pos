<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logs_integracion_factus', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_operacion', [
                'auth',
                'crear_factura',
                'consultar_estado',
                'refresh_token'
            ]);
            $table->json('request')->nullable();       // Datos enviados a Factus
            $table->json('response')->nullable();      // Respuesta de Factus
            $table->integer('codigo_http')->nullable();
            $table->boolean('exitoso')->default(false);
            $table->foreignId('venta_id')
                  ->nullable()
                  ->constrained('ventas')
                  ->onDelete('set null');
            $table->foreignId('usuario_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');

            $table->index('venta_id');
            $table->index('tipo_operacion');
            $table->index('exitoso');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_integracion_factus');
    }
};