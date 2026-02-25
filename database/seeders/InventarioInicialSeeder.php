<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Inventario;

class InventarioInicialSeeder extends Seeder
{
    public function run(): void
    {
        $productos = Producto::activos()->get();
        $sucursales = Sucursal::activas()->get();

        if ($productos->isEmpty()) {
            $this->command->warn('⚠️  No hay productos activos para crear inventario');
            return;
        }

        if ($sucursales->isEmpty()) {
            $this->command->error('❌ No hay sucursales activas');
            return;
        }

        $this->command->info('Creando inventarios iniciales...');
        $count = 0;

        foreach ($sucursales as $sucursal) {
            $this->command->info("  → Sucursal: {$sucursal->nombre}");
            
            foreach ($productos as $producto) {
                // Generar stock aleatorio para demostración
                $stockAleatorio = rand(0, 100);
                $stockMinimo = rand(5, 20);
                $costoPromedio = $producto->precio_venta * 0.6; // 60% del precio de venta

                Inventario::create([
                    'producto_id' => $producto->id,
                    'sucursal_id' => $sucursal->id,
                    'stock_actual' => $stockAleatorio,
                    'stock_minimo' => $stockMinimo,
                    'stock_maximo' => $stockMinimo * 5,
                    'costo_promedio' => $costoPromedio,
                ]);

                $count++;
            }
        }

        $this->command->info("✅ {$count} registros de inventario creados");
        $this->command->info("   Productos: {$productos->count()}");
        $this->command->info("   Sucursales: {$sucursales->count()}");
    }
}