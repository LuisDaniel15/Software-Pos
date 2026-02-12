<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codigos_estandar', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100); // "GTIN", "UNSPSC", etc.
            $table->timestamps();

            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codigos_estandar');
    }
};