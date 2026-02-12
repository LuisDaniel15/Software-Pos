<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mandatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detalle_venta_id')
                  ->constrained('detalle_ventas')
                  ->onDelete('cascade');
            $table->foreignId('tipo_documento_id')
                  ->constrained('tipos_documento_identidad')
                  ->onDelete('restrict');
            $table->string('numero_documento', 50);
            $table->smallInteger('dv')->nullable();    // ✅ INTEGER (Factus)
            $table->string('razon_social', 255)->nullable();
            $table->string('nombres', 255)->nullable();
            $table->timestamps();

            $table->index('detalle_venta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandatos');
    }
};