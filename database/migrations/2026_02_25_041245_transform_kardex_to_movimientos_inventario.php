<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Renombrar tabla
        Schema::rename('kardex', 'movimientos_inventario');
        
        // 2. Renombrar secuencia
        DB::statement('ALTER SEQUENCE kardex_id_seq RENAME TO movimientos_inventario_id_seq');

        // 3. Modificar y agregar columnas
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            // Agregar columnas nuevas de sucursales
            $table->foreignId('sucursal_origen_id')
                ->nullable()
                ->after('producto_id')
                ->constrained('sucursales')
                ->onDelete('cascade');
            
            $table->foreignId('sucursal_destino_id')
                ->nullable()
                ->after('sucursal_origen_id')
                ->constrained('sucursales')
                ->onDelete('cascade');
            
            // Agregar columna motivo (equivalente a observacion pero mejor nombre)
            $table->string('motivo', 255)->nullable()->after('costo_unitario');
            
            // Agregar columna referencia (equivalente a referencia_tipo + referencia_id)
            $table->string('referencia', 100)->nullable()->after('motivo');
            
            // Agregar precio_venta
            $table->decimal('precio_venta', 12, 2)->nullable()->after('costo_unitario');
            
            // Agregar fecha_movimiento (equivalente a created_at pero más explícito)
            $table->timestamp('fecha_movimiento')->nullable()->after('referencia');
            
            // Índices
            $table->index('sucursal_origen_id');
            $table->index('sucursal_destino_id');
            $table->index(['producto_id', 'sucursal_origen_id']);
            $table->index('fecha_movimiento');
        });

        // 4. Copiar datos de observacion a motivo
        DB::statement('UPDATE movimientos_inventario SET motivo = observacion WHERE observacion IS NOT NULL');
        
        // 5. Copiar created_at a fecha_movimiento
        DB::statement('UPDATE movimientos_inventario SET fecha_movimiento = created_at');
        
        // 6. Construir referencia desde referencia_tipo + referencia_id
        DB::statement("
            UPDATE movimientos_inventario 
            SET referencia = CONCAT(referencia_tipo, '-', referencia_id) 
            WHERE referencia_tipo IS NOT NULL AND referencia_id IS NOT NULL
        ");

        // 7. Eliminar columnas viejas que ya no necesitamos
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropColumn([
                'referencia_tipo',
                'referencia_id',
                'stock_anterior',
                'stock_nuevo',
                'observacion'
            ]);
        });

        // 8. Actualizar constraint de tipo_movimiento
        DB::statement("
            ALTER TABLE movimientos_inventario 
            DROP CONSTRAINT IF EXISTS movimientos_inventario_tipo_movimiento_check
        ");

        DB::statement("
            ALTER TABLE movimientos_inventario 
            ADD CONSTRAINT movimientos_inventario_tipo_movimiento_check 
            CHECK (tipo_movimiento IN ('entrada', 'salida', 'ajuste', 'traslado_salida', 'traslado_entrada'))
        ");
    }

    public function down(): void
    {
        // Restaurar estructura original
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            // Agregar columnas viejas
            $table->string('referencia_tipo')->nullable();
            $table->bigInteger('referencia_id')->nullable();
            $table->integer('stock_anterior')->default(0);
            $table->integer('stock_nuevo')->default(0);
            $table->text('observacion')->nullable();
            
            // Eliminar columnas nuevas
            $table->dropForeign(['sucursal_origen_id']);
            $table->dropForeign(['sucursal_destino_id']);
            $table->dropColumn([
                'sucursal_origen_id',
                'sucursal_destino_id',
                'motivo',
                'referencia',
                'precio_venta',
                'fecha_movimiento'
            ]);
        });

        // Restaurar constraint original
        DB::statement("
            ALTER TABLE movimientos_inventario 
            DROP CONSTRAINT IF EXISTS movimientos_inventario_tipo_movimiento_check
        ");

        DB::statement("
            ALTER TABLE movimientos_inventario 
            ADD CONSTRAINT movimientos_inventario_tipo_movimiento_check 
            CHECK (tipo_movimiento IN ('entrada', 'salida', 'ajuste'))
        ");

        // Renombrar tabla de vuelta
        Schema::rename('movimientos_inventario', 'kardex');
        DB::statement('ALTER SEQUENCE movimientos_inventario_id_seq RENAME TO kardex_id_seq');
    }
};