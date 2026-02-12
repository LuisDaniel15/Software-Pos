<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('establecimientos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('direccion', 255);
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->foreignId('municipio_id')
                  ->constrained('municipios')
                  ->onDelete('restrict');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establecimientos');
    }
};