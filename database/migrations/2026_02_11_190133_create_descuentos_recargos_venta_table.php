<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descuentos_recargos_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')
                  ->constrained('ventas')
                  ->onDelete('cascade');
            $table->string('codigo_concepto', 10);           // "03" (Factus)
            $table->boolean('es_recargo')->default(false);   // true/false (Factus)
            $table->string('razon', 255);
            $table->decimal('base', 10, 2);                  // base_amount (Factus)
            $table->decimal('porcentaje', 5, 2)->nullable();
            $table->decimal('monto', 10, 2);                 // amount (Factus)
            $table->timestamps();

            $table->index('venta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descuentos_recargos_venta');
    }
};