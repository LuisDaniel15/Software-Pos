<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Kardex;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Exception;

class InventarioService
{
    /**
     * Descontar stock de productos de una venta
     */
    public function descontarStockVenta(Venta $venta): void
    {
        foreach ($venta->detalles as $detalle) {
            $this->registrarSalida(
                $detalle->producto_id,
                $detalle->cantidad,
                'venta',
                $venta->id,
                $venta->usuario_id,
                "Venta {$venta->numero_venta}"
            );
        }
    }

    /**
     * Registrar entrada de inventario
     */
    public function registrarEntrada(
        int $productoId,
        int $cantidad,
        string $referenciaTipo,
        ?int $referenciaId = null,
        int $usuarioId,
        ?float $costoUnitario = null,
        ?string $observacion = null
    ): Kardex {
        return DB::transaction(function () use (
            $productoId,
            $cantidad,
            $referenciaTipo,
            $referenciaId,
            $usuarioId,
            $costoUnitario,
            $observacion
        ) {
            $producto = Producto::lockForUpdate()->findOrFail($productoId);
            $stockAnterior = $producto->stock_actual;

            // Registrar en kardex
            $kardex = Kardex::registrarMovimiento(
                $productoId,
                'entrada',
                $referenciaTipo,
                $referenciaId,
                $cantidad,
                $stockAnterior,
                $costoUnitario,
                $usuarioId,
                $observacion
            );

            // Actualizar stock del producto
            $producto->actualizarStock($cantidad, 'entrada');

            // Actualizar costo si se proporciona
            if ($costoUnitario) {
                $producto->update(['costo_compra' => $costoUnitario]);
            }

            return $kardex;
        });
    }

    /**
     * Registrar salida de inventario
     */
    public function registrarSalida(
        int $productoId,
        int $cantidad,
        string $referenciaTipo,
        ?int $referenciaId = null,
        int $usuarioId,
        ?string $observacion = null
    ): Kardex {
        return DB::transaction(function () use (
            $productoId,
            $cantidad,
            $referenciaTipo,
            $referenciaId,
            $usuarioId,
            $observacion
        ) {
            $producto = Producto::lockForUpdate()->findOrFail($productoId);

            // Validar stock disponible
            if (!$producto->tieneStockDisponible($cantidad)) {
                throw new Exception(
                    "Stock insuficiente para {$producto->nombre}. " .
                    "Disponible: {$producto->stock_actual}, Solicitado: {$cantidad}"
                );
            }

            $stockAnterior = $producto->stock_actual;

            // Registrar en kardex
            $kardex = Kardex::registrarMovimiento(
                $productoId,
                'salida',
                $referenciaTipo,
                $referenciaId,
                $cantidad,
                $stockAnterior,
                $producto->costo_compra,
                $usuarioId,
                $observacion
            );

            // Actualizar stock del producto
            $producto->actualizarStock($cantidad, 'salida');

            return $kardex;
        });
    }

    /**
     * Registrar ajuste de inventario
     */
    public function registrarAjuste(
        int $productoId,
        int $cantidadNueva,
        int $usuarioId,
        string $motivo
    ): Kardex {
        return DB::transaction(function () use (
            $productoId,
            $cantidadNueva,
            $usuarioId,
            $motivo
        ) {
            $producto = Producto::lockForUpdate()->findOrFail($productoId);
            $stockAnterior = $producto->stock_actual;

            // Registrar en kardex
            $kardex = Kardex::registrarMovimiento(
                $productoId,
                'ajuste',
                'ajuste_manual',
                null,
                $cantidadNueva,
                $stockAnterior,
                $producto->costo_compra,
                $usuarioId,
                $motivo
            );

            // Actualizar stock del producto directamente
            $producto->update(['stock_actual' => $cantidadNueva]);

            return $kardex;
        });
    }

    /**
     * Obtener productos con stock bajo
     */
    public function obtenerProductosBajoStock()
    {
        return Producto::bajoStock()
            ->activos()
            ->with(['categoria', 'unidadMedida'])
            ->get();
    }

    /**
     * Obtener kardex de un producto
     */
    public function obtenerKardexProducto(int $productoId, ?string $desde = null, ?string $hasta = null)
    {
        $query = Kardex::porProducto($productoId)
            ->with(['usuario'])
            ->orderBy('created_at', 'desc');

        if ($desde && $hasta) {
            $query->porFecha($desde, $hasta);
        }

        return $query->get();
    }

    /**
     * Calcular valor total del inventario
     */
    public function calcularValorInventario(): array
    {
        $productos = Producto::activos()
            ->whereNotNull('costo_compra')
            ->where('stock_actual', '>', 0)
            ->get();

        $valorTotal = 0;
        $cantidadProductos = 0;
        $cantidadUnidades = 0;

        foreach ($productos as $producto) {
            $valorProducto = $producto->stock_actual * $producto->costo_compra;
            $valorTotal += $valorProducto;
            $cantidadProductos++;
            $cantidadUnidades += $producto->stock_actual;
        }

        return [
            'valor_total' => $valorTotal,
            'cantidad_productos' => $cantidadProductos,
            'cantidad_unidades' => $cantidadUnidades,
            'promedio_costo' => $cantidadUnidades > 0 
                ? $valorTotal / $cantidadUnidades 
                : 0,
        ];
    }

    /**
     * Obtener movimientos de inventario del día
     */
    public function obtenerMovimientosDelDia(?string $fecha = null)
    {
        $fecha = $fecha ?? today();

        return Kardex::whereDate('created_at', $fecha)
            ->with(['producto', 'usuario'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener productos más vendidos
     */
    public function obtenerProductosMasVendidos(int $limit = 10, ?string $desde = null, ?string $hasta = null)
    {
        $query = DB::table('detalle_ventas')
            ->join('productos', 'detalle_ventas.producto_id', '=', 'productos.id')
            ->join('ventas', 'detalle_ventas.venta_id', '=', 'ventas.id')
            ->where('ventas.estado', 'completada')
            ->select(
                'productos.id',
                'productos.nombre',
                'productos.codigo_referencia',
                DB::raw('SUM(detalle_ventas.cantidad) as total_vendido'),
                DB::raw('SUM(detalle_ventas.total) as total_ingresos'),
                DB::raw('COUNT(DISTINCT ventas.id) as cantidad_ventas')
            )
            ->groupBy('productos.id', 'productos.nombre', 'productos.codigo_referencia');

        if ($desde && $hasta) {
            $query->whereBetween('ventas.fecha_venta', [$desde, $hasta]);
        }

        return $query->orderByDesc('total_vendido')
            ->limit($limit)
            ->get();
    }
}