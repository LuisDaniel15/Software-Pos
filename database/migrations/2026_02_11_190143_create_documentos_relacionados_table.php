<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_relacionados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')
                  ->constrained('ventas')
                  ->onDelete('cascade');
            $table->string('codigo_documento', 50);  // related_documents.code
            $table->string('numero_documento', 100); // related_documents.number
            $table->date('fecha_emision');            // related_documents.issue_date
            $table->timestamps();

            $table->index('venta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_relacionados');
    }
};