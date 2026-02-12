<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_documento_id')
                  ->constrained('tipos_documento_identidad')
                  ->onDelete('restrict');
            $table->string('numero_documento', 50);
            $table->smallInteger('dv')->nullable();          // ✅ INTEGER (Factus)
            $table->enum('tipo_persona', ['natural', 'juridica'])
                  ->default('natural');
            $table->string('razon_social', 255)->nullable(); // Obligatorio si jurídica
            $table->string('nombre_comercial', 255)->nullable();
            $table->string('nombres', 255)->nullable();      // Obligatorio si natural
            $table->string('apellidos', 255)->nullable();
            $table->string('graphic_representation_name', 255)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email', 100)->nullable();
            
            $table->foreignId('municipio_id')
                  ->nullable()
                  ->constrained('municipios')
                  ->onDelete('set null');

            $table->foreignId('tipo_organizacion_id')
                  ->constrained('tipos_organizacion')
                  ->onDelete('restrict');

            $table->foreignId('tributo_cliente_id')
                  ->constrained('tributos_cliente')
                  ->onDelete('restrict');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('numero_documento');
            $table->index('email');
            $table->index('tipo_persona');
            $table->index('activo');
            $table->unique(['tipo_documento_id', 'numero_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};