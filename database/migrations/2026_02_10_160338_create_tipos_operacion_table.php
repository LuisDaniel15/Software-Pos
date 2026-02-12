<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_operacion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique(); // "10", "11", "12"
            $table->string('descripcion', 100);     // "Estándar", "Mandatos"
            $table->timestamps();

            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_operacion');
    }
};