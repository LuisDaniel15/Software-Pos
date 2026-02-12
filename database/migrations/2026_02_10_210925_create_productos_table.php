<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')
                  ->nullable()
                  ->constrained('categorias')
                  ->onDelete('set null');
            $table->string('codigo_referencia', 100)->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->decimal('precio_venta', 10, 2);           // CON IVA incluido
            $table->string('porcentaje_iva', 10)
                  ->default('0.00');                          // ✅ STRING "19.00" (Factus)
            $table->decimal('costo_compra', 10, 2)->nullable();
            $table->decimal('stock_actual', 10, 2)->default(0);
            $table->decimal('stock_minimo', 10, 2)->nullable();
            $table->foreignId('unidad_medida_id')
                  ->constrained('unidades_medida')
                  ->onDelete('restrict');
            $table->foreignId('codigo_estandar_id')
                  ->constrained('codigos_estandar')
                  ->onDelete('restrict');
            $table->string('codigo_estandar_valor', 100)
                  ->nullable();                               // Código de barras
            $table->foreignId('tributo_id')
                  ->constrained('tributos')
                  ->onDelete('restrict');
            $table->tinyInteger('es_excluido')->default(0);   // ✅ 0/1 (Factus)
            $table->boolean('permite_mandato')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('codigo_referencia');
            $table->index('codigo_estandar_valor');
            $table->index('categoria_id');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};