<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rangos_numeracion', function (Blueprint $table) {
            $table->id();
            $table->string('document', 100);       // "Factura de Venta"
            $table->string('prefijo', 20);          // "SETP"
            $table->bigInteger('desde');            // 990000000
            $table->bigInteger('hasta');            // 995000000
            $table->bigInteger('consecutivo_actual')->default(0); // "current"
            $table->string('numero_resolucion', 100)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('technical_key', 255)->nullable();
            $table->boolean('is_expired')->default(false);
            $table->tinyInteger('is_active')->default(1); // AQUI HAY QUE REVISAR ALGO  "is_active": 1,
            $table->softDeletes();
            $table->timestamps();

            $table->index(['document', 'is_active']);
            $table->index('prefijo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rangos_numeracion');
    }
};