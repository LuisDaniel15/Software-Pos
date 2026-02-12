<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')
                  ->constrained('ventas')
                  ->onDelete('cascade');
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->onDelete('restrict');

            // Snapshot del producto al momento de la venta
            $table->string('codigo_referencia', 100);
            $table->string('nombre_producto', 255);
            $table->integer('cantidad');                      // ✅ INTEGER (Factus)
            $table->decimal('precio_unitario', 10, 2);        // CON IVA
            $table->string('porcentaje_iva', 10);             // ✅ STRING "19.00" (Factus)
            $table->decimal('porcentaje_descuento', 5, 2)->default(0);

            // Cálculos
            $table->decimal('precio_base', 10, 2);            // SIN IVA
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);               // Sin IVA
            $table->decimal('total_iva', 10, 2);
            $table->decimal('total', 10, 2);

            // Datos DIAN
            $table->foreignId('unidad_medida_id')
                  ->constrained('unidades_medida')
                  ->onDelete('restrict');
            $table->foreignId('codigo_estandar_id')
                  ->constrained('codigos_estandar')
                  ->onDelete('restrict');
            $table->foreignId('tributo_id')
                  ->constrained('tributos')
                  ->onDelete('restrict');
            $table->tinyInteger('es_excluido')->default(0);   // ✅ 0/1 (Factus)
            $table->text('nota_item')->nullable();
            $table->string('scheme_id', 10)->nullable();      // Para mandatos

            $table->timestamps();

            $table->index('venta_id');
            $table->index('producto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_ventas');
    }
};