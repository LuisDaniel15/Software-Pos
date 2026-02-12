<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_caja_id')
                  ->nullable()
                  ->constrained('turnos_caja')
                  ->onDelete('set null');
            $table->foreignId('cliente_id')
                  ->constrained('clientes')
                  ->onDelete('restrict');
            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->onDelete('restrict');
            $table->foreignId('establecimiento_id')
                  ->nullable()
                  ->constrained('establecimientos')
                  ->onDelete('set null');

            // Numeración interna
            $table->string('numero_venta', 50)->unique();
            $table->string('reference_code', 100)->unique(); // Para Factus

            // Datos DIAN/Factus
            $table->foreignId('tipo_documento_id')
                  ->constrained('tipos_documento_factura')
                  ->onDelete('restrict');
            $table->foreignId('rango_numeracion_id')
                  ->nullable()
                  ->constrained('rangos_numeracion')
                  ->onDelete('set null');
            $table->string('numero_factura_dian', 100)->nullable()->unique();
            $table->string('cufe', 255)->nullable()->unique();
            $table->text('qr_url')->nullable();
            $table->longText('qr_image')->nullable();         // Base64
            $table->enum('estado_dian', [
                'pendiente',
                'validada',
                'rechazada'
            ])->default('pendiente');
            $table->timestamp('fecha_validacion_dian')->nullable();
            $table->json('errores_dian')->nullable();
            $table->json('respuesta_factus')->nullable();      // Respuesta completa

            // Pago
            $table->timestamp('fecha_venta');
            $table->foreignId('forma_pago_id')
                  ->constrained('formas_pago')
                  ->onDelete('restrict');
            $table->date('fecha_vencimiento')->nullable();     // Solo crédito
            $table->foreignId('metodo_pago_id')
                  ->constrained('metodos_pago')
                  ->onDelete('restrict');

            // Campos adicionales Factus
            $table->foreignId('tipo_operacion_id')
                  ->constrained('tipos_operacion')
                  ->onDelete('restrict');
            $table->string('orden_numero', 100)->nullable();   // order_reference
            $table->date('orden_fecha')->nullable();
            $table->date('periodo_inicio')->nullable();        // billing_period
            $table->time('periodo_hora_inicio')->nullable();
            $table->date('periodo_fin')->nullable();
            $table->time('periodo_hora_fin')->nullable();

            // Totales
            $table->decimal('subtotal', 10, 2);               // Sin IVA
            $table->decimal('total_iva', 10, 2);
            $table->decimal('total_descuentos', 10, 2)->default(0);
            $table->decimal('total_recargos', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // Verificación Factus
            $table->decimal('gross_value', 10, 2)->nullable();
            $table->decimal('taxable_amount', 10, 2)->nullable();

            $table->enum('estado', [
                'completada',
                'anulada',
                'pendiente'
            ])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->boolean('enviar_email')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('cliente_id');
            $table->index('usuario_id');
            $table->index('fecha_venta');
            $table->index('estado_dian');
            $table->index('tipo_documento_id');
            $table->index('estado');
            $table->index('turno_caja_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};