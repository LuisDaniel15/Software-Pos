<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tributos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_dian', 10)->unique();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index('codigo_dian');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tributos');
    }
};