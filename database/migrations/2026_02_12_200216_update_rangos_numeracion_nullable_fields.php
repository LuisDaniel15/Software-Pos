<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rangos_numeracion', function (Blueprint $table) {
            // Hacer nullable los campos que Factus puede devolver como null
            $table->bigInteger('desde')->nullable()->change();
            $table->bigInteger('hasta')->nullable()->change();
            $table->date('fecha_inicio')->nullable()->change();
            $table->date('fecha_fin')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rangos_numeracion', function (Blueprint $table) {
            $table->bigInteger('desde')->nullable(false)->change();
            $table->bigInteger('hasta')->nullable(false)->change();
            $table->date('fecha_inicio')->nullable(false)->change();
            $table->date('fecha_fin')->nullable(false)->change();
        });
    }
};