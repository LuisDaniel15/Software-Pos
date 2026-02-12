<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_organizacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100); // "Persona Jurídica", "Persona Natural"
            $table->timestamps();

            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_organizacion');
    }
};