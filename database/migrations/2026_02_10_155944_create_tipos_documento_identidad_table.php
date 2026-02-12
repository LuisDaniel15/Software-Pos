<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_documento_identidad', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100); // "Cédula de ciudadanía", "NIT"
            $table->timestamps();

            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_documento_identidad');
    }
};