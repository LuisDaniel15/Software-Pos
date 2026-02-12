<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retenciones_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')
                  ->constrained('ventas')
                  ->onDelete('cascade');
            $table->string('codigo_tributo', 10);    // "05" ReteIVA, "06" ReteRenta
            $table->string('nombre_retencion', 100);
            $table->decimal('valor_total', 10, 2);
            $table->timestamps();

            $table->index('venta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retenciones_venta');
    }
};