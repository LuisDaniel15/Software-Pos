<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tributos_cliente', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10); // "01", "ZZ"
            $table->string('nombre', 100); // "IVA", "No aplica"
            $table->timestamps();

            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tributos_cliente');
    }
};