<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_documento_factura', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique(); // "01", "03"
            $table->string('descripcion', 100);     // "Factura electrónica de venta"
            $table->timestamps();

            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_documento_factura');
    }
};