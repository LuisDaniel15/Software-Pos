<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombre')->after('id');
            $table->enum('rol', ['admin', 'cajero', 'supervisor'])
                  ->default('cajero')
                  ->after('password');
            $table->boolean('activo')
                  ->default(true)
                  ->after('rol');

            $table->index('rol');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['rol']);
            $table->dropIndex(['activo']);
            $table->dropColumn(['nombre', 'rol', 'activo']);
        });
    }
};