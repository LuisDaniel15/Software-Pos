<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retenciones_detalle_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detalle_venta_id')
                  ->constrained('detalle_ventas')
                  ->onDelete('cascade');
            $table->string('codigo_retencion', 10);  // "05", "06" (string Factus)
            $table->string('nombre_retencion', 100);
            $table->decimal('porcentaje', 5, 2);      // ✅ float → decimal
            $table->decimal('valor', 10, 2);
            $table->timestamps();

            $table->index('detalle_venta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retenciones_detalle_venta');
    }
};