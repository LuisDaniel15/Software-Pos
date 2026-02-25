<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LimpiarDatosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->warn('⚠️  Limpiando datos de prueba...');

        // Desactivar temporalmente las restricciones de FK
        DB::statement('SET CONSTRAINTS ALL DEFERRED');

        // Limpiar en orden de dependencias (de hijo a padre)
        
        // 1. Detalles de ventas (depende de ventas)
        $this->command->info('  → Limpiando detalles de ventas...');
        DB::table('detalle_ventas')->truncate();
        
        // 2. Retenciones de ventas
        $this->command->info('  → Limpiando retenciones...');
        DB::table('retenciones_venta')->truncate();
        
        // 3. Descuentos y recargos
        $this->command->info('  → Limpiando descuentos y recargos...');
        DB::table('descuentos_recargos_venta')->truncate();
        
        // 4. Documentos relacionados
        $this->command->info('  → Limpiando documentos relacionados...');
        DB::table('documentos_relacionados')->truncate();
        
        // 5. Ventas
        $this->command->info('  → Limpiando ventas...');
        DB::table('ventas')->truncate();
        
        // 6. Movimientos de caja (depende de turnos_caja)
        $this->command->info('  → Limpiando movimientos de caja...');
        DB::table('movimientos_caja')->truncate();
        
        // 7. Turnos de caja
        $this->command->info('  → Limpiando turnos de caja...');
        DB::table('turnos_caja')->truncate();
        
        // 8. Movimientos de inventario
        $this->command->info('  → Limpiando movimientos de inventario...');
        DB::table('movimientos_inventario')->truncate();
        
        // 9. Inventarios
        $this->command->info('  → Limpiando inventarios...');
        DB::table('inventarios')->truncate();

        // Resetear secuencias para que los IDs empiecen en 1
        $this->command->info('  → Reseteando secuencias...');
        
        $secuencias = [
            'ventas',
            'detalles_venta',
            'retenciones_venta',
            'descuentos_recargos_venta',
            'documentos_relacionados',
            'turnos_caja',
            'movimientos_caja',
            'movimientos_inventario',
            'inventarios',
        ];

        foreach ($secuencias as $tabla) {
            try {
                DB::statement("ALTER SEQUENCE {$tabla}_id_seq RESTART WITH 1");
            } catch (\Exception $e) {
                $this->command->warn("    ⚠️  No se pudo resetear secuencia de {$tabla}");
            }
        }

        // Reactivar restricciones
        DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

        $this->command->info('');
        $this->command->info('✅ Datos de prueba limpiados exitosamente');
        $this->command->info('');
        $this->command->info('📊 Resumen:');
        $this->command->info('   • Ventas y detalles eliminados');
        $this->command->info('   • Turnos y movimientos de caja eliminados');
        $this->command->info('   • Movimientos e inventarios eliminados');
        $this->command->info('   • Secuencias reseteadas a 1');
        $this->command->info('');
        $this->command->warn('⚠️  Los catálogos, productos, clientes y usuarios NO fueron afectados');
    }
}