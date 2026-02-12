<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_original_id')
                  ->constrained('ventas')
                  ->onDelete('restrict');
            $table->foreignId('venta_nota_id')
                  ->constrained('ventas')
                  ->onDelete('cascade');
            $table->text('motivo');
            $table->timestamps();

            $table->index('venta_original_id');
            $table->index('venta_nota_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_credito');
    }
};