<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
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
        $sucursalId = $venta->usuario->sucursal_id;

        if (!$sucursalId) {
            throw new Exception('El usuario de la venta no tiene sucursal asignada');
        }

        foreach ($venta->detalles as $detalle) {
            $this->actualizarStock(
                $detalle->producto_id,
                $sucursalId,
                $detalle->cantidad,
                'salida',
                null,
                "Venta {$venta->numero_venta}",
                $venta->numero_venta,
                $venta->usuario_id
            );
        }
    }

    /**
     * Actualizar stock de un producto en una sucursal
     */
    public function actualizarStock(
        int $productoId,
        int $sucursalId,
        float $cantidad,
        string $tipoMovimiento,
        ?float $costoUnitario = null,
        ?string $motivo = null,
        ?string $referencia = null,
        ?int $usuarioId = null
    ): array {
        return DB::transaction(function () use (
            $productoId,
            $sucursalId,
            $cantidad,
            $tipoMovimiento,
            $costoUnitario,
            $motivo,
            $referencia,
            $usuarioId
        ) {
            // 1. Validar datos
            $this->validarDatos($productoId, $sucursalId, $cantidad, $tipoMovimiento);

            // 2. Bloquear registro de inventario (SELECT FOR UPDATE)
            $inventario = Inventario::where('producto_id', $productoId)
                ->where('sucursal_id', $sucursalId)
                ->lockForUpdate()
                ->first();

            // 3. Crear inventario si no existe
            if (!$inventario) {
                $inventario = Inventario::create([
                    'producto_id' => $productoId,
                    'sucursal_id' => $sucursalId,
                    'stock_actual' => 0,
                    'stock_minimo' => 0,
                    'costo_promedio' => $costoUnitario ?? 0,
                ]);
            }

            // 4. Validar stock disponible para salidas
            if (in_array($tipoMovimiento, ['salida', 'traslado_salida'])) {
                if ($inventario->stock_actual < $cantidad) {
                    throw new Exception(
                        "Stock insuficiente. Disponible: {$inventario->stock_actual}, Requerido: {$cantidad}"
                    );
                }
            }

            $stockAnterior = $inventario->stock_actual;

            // 5. Actualizar stock según tipo de movimiento
            switch ($tipoMovimiento) {
                case 'entrada':
                case 'traslado_entrada':
                    $inventario->incrementarStock($cantidad, $costoUnitario);
                    break;

                case 'salida':
                case 'traslado_salida':
                    $inventario->decrementarStock($cantidad);
                    break;

                case 'ajuste':
                    // Para ajustes, la cantidad representa el nuevo stock
                    $inventario->ajustarStock($cantidad);
                    break;

                default:
                    throw new Exception("Tipo de movimiento no válido: {$tipoMovimiento}");
            }

            // 6. Registrar movimiento en kardex
            $movimiento = MovimientoInventario::create([
                'producto_id' => $productoId,
                'sucursal_origen_id' => $sucursalId,
                'tipo_movimiento' => $tipoMovimiento,
                'cantidad' => $tipoMovimiento === 'ajuste' 
                    ? abs($cantidad - $stockAnterior) 
                    : $cantidad,
                'costo_unitario' => $costoUnitario,
                'motivo' => $motivo ?? "Movimiento de {$tipoMovimiento}",
                'referencia' => $referencia,
                'fecha_movimiento' => now(),
                'usuario_id' => $usuarioId ?? auth()->id(),
            ]);

            return [
                'inventario' => $inventario->fresh(),
                'movimiento' => $movimiento->fresh(['producto', 'usuario', 'sucursalOrigen']),
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $inventario->stock_actual,
            ];
        });
    }

    /**
     * Registrar entrada de mercancía
     */
    public function registrarEntrada(
        int $productoId,
        int $sucursalId,
        float $cantidad,
        float $costoUnitario,
        string $motivo,
        ?string $referencia = null,
        ?int $usuarioId = null
    ): array {
        return $this->actualizarStock(
            $productoId,
            $sucursalId,
            $cantidad,
            'entrada',
            $costoUnitario,
            $motivo,
            $referencia,
            $usuarioId
        );
    }

    /**
     * Registrar salida de mercancía
     */
    public function registrarSalida(
        int $productoId,
        int $sucursalId,
        float $cantidad,
        string $motivo,
        ?string $referencia = null,
        ?int $usuarioId = null
    ): array {
        return $this->actualizarStock(
            $productoId,
            $sucursalId,
            $cantidad,
            'salida',
            null,
            $motivo,
            $referencia,
            $usuarioId
        );
    }

    /**
     * Registrar ajuste de inventario
     */
    public function registrarAjuste(
        int $productoId,
        int $sucursalId,
        float $nuevoStock,
        string $motivo,
        ?int $usuarioId = null
    ): array {
        return $this->actualizarStock(
            $productoId,
            $sucursalId,
            $nuevoStock,
            'ajuste',
            null,
            $motivo,
            "AJUSTE-" . now()->format('YmdHis'),
            $usuarioId
        );
    }

    /**
     * Trasladar productos entre sucursales
     */
    public function trasladarProducto(
        int $productoId,
        int $sucursalOrigenId,
        int $sucursalDestinoId,
        float $cantidad,
        string $motivo,
        ?int $usuarioId = null
    ): array {
        return DB::transaction(function () use (
            $productoId,
            $sucursalOrigenId,
            $sucursalDestinoId,
            $cantidad,
            $motivo,
            $usuarioId
        ) {
            // Validar que las sucursales sean diferentes
            if ($sucursalOrigenId === $sucursalDestinoId) {
                throw new Exception('La sucursal origen y destino deben ser diferentes');
            }

            // Validar que las sucursales existan
            $sucursalOrigen = Sucursal::findOrFail($sucursalOrigenId);
            $sucursalDestino = Sucursal::findOrFail($sucursalDestinoId);
            $producto = Producto::findOrFail($productoId);

            // Bloquear inventario origen
            $invOrigen = Inventario::where('producto_id', $productoId)
                ->where('sucursal_id', $sucursalOrigenId)
                ->lockForUpdate()
                ->first();

            if (!$invOrigen) {
                throw new Exception(
                    "El producto {$producto->nombre} no tiene inventario en {$sucursalOrigen->nombre}"
                );
            }

            // Validar stock disponible
            if ($invOrigen->stock_actual < $cantidad) {
                throw new Exception(
                    "Stock insuficiente en {$sucursalOrigen->nombre}. " .
                    "Disponible: {$invOrigen->stock_actual}, Requerido: {$cantidad}"
                );
            }

            // Bloquear inventario destino
            $invDestino = Inventario::where('producto_id', $productoId)
                ->where('sucursal_id', $sucursalDestinoId)
                ->lockForUpdate()
                ->first();

            if (!$invDestino) {
                $invDestino = Inventario::create([
                    'producto_id' => $productoId,
                    'sucursal_id' => $sucursalDestinoId,
                    'stock_actual' => 0,
                    'stock_minimo' => $invOrigen->stock_minimo,
                    'costo_promedio' => $invOrigen->costo_promedio,
                ]);
            }

            $stockOrigenAnterior = $invOrigen->stock_actual;
            $stockDestinoAnterior = $invDestino->stock_actual;

            // Restar de origen
            $invOrigen->decrementarStock($cantidad);

            // Sumar a destino
            $invDestino->incrementarStock($cantidad, $invOrigen->costo_promedio);

            // Generar referencia única
            $referencia = "TRASLADO-" . now()->format('YmdHis');

            // Registrar movimiento SALIDA en origen
            $movimientoSalida = MovimientoInventario::create([
                'producto_id' => $productoId,
                'sucursal_origen_id' => $sucursalOrigenId,
                'sucursal_destino_id' => $sucursalDestinoId,
                'tipo_movimiento' => 'traslado_salida',
                'cantidad' => $cantidad,
                'costo_unitario' => $invOrigen->costo_promedio,
                'motivo' => $motivo,
                'referencia' => $referencia,
                'fecha_movimiento' => now(),
                'usuario_id' => $usuarioId ?? auth()->id(),
            ]);

            // Registrar movimiento ENTRADA en destino
            $movimientoEntrada = MovimientoInventario::create([
                'producto_id' => $productoId,
                'sucursal_origen_id' => $sucursalOrigenId,
                'sucursal_destino_id' => $sucursalDestinoId,
                'tipo_movimiento' => 'traslado_entrada',
                'cantidad' => $cantidad,
                'costo_unitario' => $invOrigen->costo_promedio,
                'motivo' => $motivo,
                'referencia' => $referencia,
                'fecha_movimiento' => now(),
                'usuario_id' => $usuarioId ?? auth()->id(),
            ]);

            return [
                'traslado' => [
                    'referencia' => $referencia,
                    'producto' => $producto->nombre,
                    'cantidad' => $cantidad,
                    'sucursal_origen' => $sucursalOrigen->nombre,
                    'sucursal_destino' => $sucursalDestino->nombre,
                ],
                'inventario_origen' => [
                    'stock_anterior' => $stockOrigenAnterior,
                    'stock_nuevo' => $invOrigen->stock_actual,
                ],
                'inventario_destino' => [
                    'stock_anterior' => $stockDestinoAnterior,
                    'stock_nuevo' => $invDestino->stock_actual,
                ],
                'movimientos' => [
                    'salida' => $movimientoSalida->fresh(['producto', 'usuario', 'sucursalOrigen', 'sucursalDestino']),
                    'entrada' => $movimientoEntrada->fresh(['producto', 'usuario', 'sucursalOrigen', 'sucursalDestino']),
                ],
            ];
        });
    }

    /**
     * Obtener productos con bajo stock en una sucursal
     */
    public function obtenerProductosBajoStock(int $sucursalId)
    {
        return Inventario::porSucursal($sucursalId)
            ->bajoStock()
            ->with(['producto.categoria', 'producto.unidadMedida'])
            ->get()
            ->map(function($inventario) {
                $producto = $inventario->producto;
                $producto->stock_actual = $inventario->stock_actual;
                $producto->stock_minimo = $inventario->stock_minimo;
                return $producto;
            });
    }

    /**
     * Obtener kardex de un producto en una sucursal
     */
    public function obtenerKardexProducto(int $productoId, int $sucursalId, ?string $desde = null, ?string $hasta = null)
    {
        return MovimientoInventario::obtenerKardex($productoId, $sucursalId, $desde, $hasta);
    }

    /**
     * Calcular valor total del inventario de una sucursal
     */
    public function calcularValorInventario(int $sucursalId): array
    {
        $inventarios = Inventario::porSucursal($sucursalId)
            ->conStock()
            ->get();

        $valorTotal = 0;
        $cantidadProductos = 0;
        $cantidadUnidades = 0;

        foreach ($inventarios as $inventario) {
            $valorProducto = $inventario->stock_actual * $inventario->costo_promedio;
            $valorTotal += $valorProducto;
            $cantidadProductos++;
            $cantidadUnidades += $inventario->stock_actual;
        }

        return [
            'valor_total' => round($valorTotal, 2),
            'cantidad_productos' => $cantidadProductos,
            'cantidad_unidades' => $cantidadUnidades,
            'promedio_costo' => $cantidadUnidades > 0 
                ? round($valorTotal / $cantidadUnidades, 2)
                : 0,
        ];
    }

    /**
     * Obtener movimientos del día en una sucursal
     */
    public function obtenerMovimientosDelDia(int $sucursalId, ?string $fecha = null)
    {
        $fecha = $fecha ?? today();

        return MovimientoInventario::whereDate('fecha_movimiento', $fecha)
            ->porSucursal($sucursalId)
            ->with(['producto', 'usuario', 'sucursalOrigen', 'sucursalDestino'])
            ->orderBy('fecha_movimiento', 'desc')
            ->get();
    }

    /**
     * Validar datos de entrada
     */
    private function validarDatos(
        int $productoId,
        int $sucursalId,
        float $cantidad,
        string $tipoMovimiento
    ): void {
        if (!Producto::where('id', $productoId)->exists()) {
            throw new Exception("Producto con ID {$productoId} no existe");
        }

        if (!Sucursal::where('id', $sucursalId)->exists()) {
            throw new Exception("Sucursal con ID {$sucursalId} no existe");
        }

        if ($cantidad <= 0 && $tipoMovimiento !== 'ajuste') {
            throw new Exception("La cantidad debe ser mayor a 0");
        }

        $tiposValidos = ['entrada', 'salida', 'ajuste', 'traslado_entrada', 'traslado_salida'];
        if (!in_array($tipoMovimiento, $tiposValidos)) {
            throw new Exception("Tipo de movimiento no válido: {$tipoMovimiento}");
        }
    }

    /**
     * Validar disponibilidad de stock
     */
    public function validarDisponibilidad(int $productoId, int $sucursalId, float $cantidadRequerida): bool
    {
        $inventario = Inventario::where('producto_id', $productoId)
            ->where('sucursal_id', $sucursalId)
            ->first();

        if (!$inventario) {
            return false;
        }

        return $inventario->stock_actual >= $cantidadRequerida;
    }

    /**
     * Obtener stock disponible
     */
    public function obtenerStockDisponible(int $productoId, int $sucursalId): float
    {
        $inventario = Inventario::where('producto_id', $productoId)
            ->where('sucursal_id', $sucursalId)
            ->first();

        return $inventario ? $inventario->stock_actual : 0;
    }
}