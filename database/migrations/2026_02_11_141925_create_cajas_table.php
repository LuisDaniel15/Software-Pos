<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->foreignId('establecimiento_id')
                  ->nullable()
                  ->constrained('establecimientos')
                  ->onDelete('set null');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index('activa');
            $table->index('codigo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};