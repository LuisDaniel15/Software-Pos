<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kardex', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->onDelete('cascade');
            $table->enum('tipo_movimiento', ['entrada', 'salida', 'ajuste']);
            $table->enum('referencia_tipo', [
                'compra',
                'venta',
                'ajuste_manual',
                'devolucion'
            ]);
            $table->unsignedBigInteger('referencia_id')->nullable(); // ID venta o compra
            $table->integer('cantidad');                             // ✅ INTEGER
            $table->decimal('stock_anterior', 10, 2);
            $table->decimal('stock_nuevo', 10, 2);
            $table->decimal('costo_unitario', 10, 2)->nullable();
            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->onDelete('restrict');
            $table->text('observacion')->nullable();
            $table->timestamp('created_at');

            $table->index('producto_id');
            $table->index('tipo_movimiento');
            $table->index(['referencia_tipo', 'referencia_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kardex');
    }
};