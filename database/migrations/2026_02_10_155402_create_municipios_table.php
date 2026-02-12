<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_dian', 10)->unique();
            $table->string('nombre', 100);
            $table->string('departamento', 100);
            $table->timestamps();

            $table->index('codigo_dian');
            $table->index('departamento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};