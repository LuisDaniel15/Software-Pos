<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')
                ->constrained('productos')
                ->onDelete('cascade');
            $table->foreignId('sucursal_id')
                ->constrained('sucursales')
                ->onDelete('cascade');
            // $table->decimal('stock_actual', 12, 2)->default(0);
            $table->decimal('stock_minimo', 12, 2)->default(0);
            $table->decimal('stock_maximo', 12, 2)->nullable();
            $table->decimal('costo_promedio', 12, 2)->default(0);
            $table->timestamp('ultima_entrada')->nullable();
            $table->timestamp('ultima_salida')->nullable();
            $table->timestamps();
            $table->decimal('stock_actual', 12, 2)->default(0);
            // Constraints
            $table->unique(['producto_id', 'sucursal_id']);
            
            // Índices
            $table->index('producto_id');
            $table->index('sucursal_id');
            $table->index(['sucursal_id', 'stock_actual']); // Para consultas de stock bajo
            $table->index('updated_at'); // Para auditoría
        });

        // Índice condicional para productos con stock bajo
        DB::statement('
            CREATE INDEX idx_inventarios_stock_bajo 
            ON inventarios (sucursal_id, producto_id) 
            WHERE stock_actual <= stock_minimo
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};