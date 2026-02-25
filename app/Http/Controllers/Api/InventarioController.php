<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Exception;

class InventarioController extends Controller
{
    protected InventarioService $inventarioService;

    public function __construct(InventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /**
     * Obtener resumen de stock de la sucursal del usuario
     */
    public function resumenStock(Request $request)
    {
        $sucursalId = $request->user()->sucursal_id;

        if (!$sucursalId) {
            return response()->json([
                'message' => 'Usuario no tiene sucursal asignada'
            ], 400);
        }

        $resumen = $this->inventarioService->calcularValorInventario($sucursalId);
        
        // Agregar contadores adicionales
        $productosBajoStock = Inventario::porSucursal($sucursalId)->bajoStock()->count();
        $productosSinStock = Inventario::porSucursal($sucursalId)->sinStock()->count();

        return response()->json([
            'total_productos' => $resumen['cantidad_productos'],
            'productos_bajo_stock' => $productosBajoStock,
            'productos_sin_stock' => $productosSinStock,
            'valor_total_inventario' => $resumen['valor_total'],
            'cantidad_unidades' => $resumen['cantidad_unidades'],
            'promedio_costo' => $resumen['promedio_costo'],
        ]);
    }

    /**
     * Registrar movimiento de inventario
     */
    public function registrarMovimiento(Request $request)
    {
        try {
            $validated = $request->validate([
                'tipo' => 'required|in:ingreso,egreso',
                'producto_id' => 'required|exists:productos,id',
                'cantidad' => 'required|numeric|min:0.01',
                'costo_unitario' => 'nullable|numeric|min:0',
                'motivo' => 'required|string|max:255',
                'referencia' => 'nullable|string|max:100',
            ]);

            $sucursalId = $request->user()->sucursal_id;

            if (!$sucursalId) {
                return response()->json([
                    'message' => 'Usuario no tiene sucursal asignada'
                ], 400);
            }

            // Mapear tipo front->back
            $tipoMovimiento = $validated['tipo'] === 'ingreso' ? 'entrada' : 'salida';

            $resultado = $this->inventarioService->actualizarStock(
                $validated['producto_id'],
                $sucursalId,
                $validated['cantidad'],
                $tipoMovimiento,
                $validated['costo_unitario'] ?? null,
                $validated['motivo'],
                $validated['referencia'] ?? null,
                $request->user()->id
            );

            return response()->json([
                'message' => 'Movimiento registrado exitosamente',
                'movimiento' => $resultado['movimiento'],
                'inventario' => $resultado['inventario'],
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al registrar movimiento',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Trasladar producto entre sucursales
     */
    public function trasladarProducto(Request $request)
    {
        try {
            $validated = $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'sucursal_destino_id' => 'required|exists:sucursales,id',
                'cantidad' => 'required|numeric|min:0.01',
                'motivo' => 'required|string|max:255',
            ]);

            $sucursalOrigenId = $request->user()->sucursal_id;

            if (!$sucursalOrigenId) {
                return response()->json([
                    'message' => 'Usuario no tiene sucursal asignada'
                ], 400);
            }

            $resultado = $this->inventarioService->trasladarProducto(
                $validated['producto_id'],
                $sucursalOrigenId,
                $validated['sucursal_destino_id'],
                $validated['cantidad'],
                $validated['motivo'],
                $request->user()->id
            );

            return response()->json([
                'message' => 'Traslado realizado exitosamente',
                'data' => $resultado,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al realizar traslado',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ajustar inventario
     */
    public function ajustarInventario(Request $request)
    {
        try {
            $validated = $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'nuevo_stock' => 'required|numeric|min:0',
                'motivo' => 'required|string|max:255',
            ]);

            $sucursalId = $request->user()->sucursal_id;

            if (!$sucursalId) {
                return response()->json([
                    'message' => 'Usuario no tiene sucursal asignada'
                ], 400);
            }

            $resultado = $this->inventarioService->registrarAjuste(
                $validated['producto_id'],
                $sucursalId,
                $validated['nuevo_stock'],
                $validated['motivo'],
                $request->user()->id
            );

            return response()->json([
                'message' => 'Inventario ajustado exitosamente',
                'data' => $resultado,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al ajustar inventario',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Listar productos con bajo stock
     */
    public function productosBajoStock(Request $request)
    {
        $sucursalId = $request->user()->sucursal_id;

        if (!$sucursalId) {
            return response()->json([
                'message' => 'Usuario no tiene sucursal asignada'
            ], 400);
        }

        $productos = $this->inventarioService->obtenerProductosBajoStock($sucursalId);

        return response()->json([
            'data' => $productos
        ]);
    }

    /**
     * Listar productos sin stock
     */
    public function productosSinStock(Request $request)
    {
        $sucursalId = $request->user()->sucursal_id;

        if (!$sucursalId) {
            return response()->json([
                'message' => 'Usuario no tiene sucursal asignada'
            ], 400);
        }

        $productos = Inventario::porSucursal($sucursalId)
            ->sinStock()
            ->with(['producto.categoria', 'producto.unidadMedida'])
            ->get()
            ->map(function($inventario) {
                $producto = $inventario->producto;
                $producto->stock_actual = $inventario->stock_actual;
                $producto->stock_minimo = $inventario->stock_minimo;
                return $producto;
            });

        return response()->json([
            'data' => $productos
        ]);
    }

    /**
     * Obtener kardex de un producto
     */
    public function obtenerKardex(Request $request, int $productoId)
    {
        $sucursalId = $request->user()->sucursal_id;

        if (!$sucursalId) {
            return response()->json([
                'message' => 'Usuario no tiene sucursal asignada'
            ], 400);
        }

        $desde = $request->input('desde');
        $hasta = $request->input('hasta', now()->toDateString());

        $kardex = $this->inventarioService->obtenerKardexProducto(
            $productoId,
            $sucursalId,
            $desde,
            $hasta
        );

        $producto = \App\Models\Producto::findOrFail($productoId);

        return response()->json([
            'producto' => [
                'id' => $producto->id,
                'codigo_referencia' => $producto->codigo_referencia,
                'nombre' => $producto->nombre,
            ],
            'kardex' => $kardex,
        ]);
    }

    /**
     * Listar movimientos de inventario
     */
    public function listarMovimientos(Request $request)
    {
        $sucursalId = $request->user()->sucursal_id;

        if (!$sucursalId) {
            return response()->json([
                'message' => 'Usuario no tiene sucursal asignada'
            ], 400);
        }

        $query = MovimientoInventario::with([
            'producto',
            'usuario',
            'sucursalOrigen',
            'sucursalDestino'
        ])->porSucursal($sucursalId);

        // Filtros
        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo_movimiento', $request->tipo_movimiento);
        }

        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('producto', function($q) use ($search) {
                $q->where('nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('codigo_referencia', 'ILIKE', "%{$search}%");
            });
        }

        $movimientos = $query->orderBy('fecha_movimiento', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $movimientos
        ]);
    }

    /**
     * Movimientos del día
     */
    public function movimientosDelDia(Request $request)
    {
        $sucursalId = $request->user()->sucursal_id;

        if (!$sucursalId) {
            return response()->json([
                'message' => 'Usuario no tiene sucursal asignada'
            ], 400);
        }

        $movimientos = $this->inventarioService->obtenerMovimientosDelDia(
            $sucursalId,
            $request->fecha ?? null
        );

        return response()->json([
            'data' => $movimientos
        ]);
    }

    /**
     * Validar disponibilidad de stock
     */
    public function validarDisponibilidad(Request $request)
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|numeric|min:0.01',
        ]);

        $sucursalId = $request->user()->sucursal_id;

        if (!$sucursalId) {
            return response()->json([
                'message' => 'Usuario no tiene sucursal asignada',
                'disponible' => false,
            ], 400);
        }

        $disponible = $this->inventarioService->validarDisponibilidad(
            $validated['producto_id'],
            $sucursalId,
            $validated['cantidad']
        );

        $stockActual = $this->inventarioService->obtenerStockDisponible(
            $validated['producto_id'],
            $sucursalId
        );

        return response()->json([
            'disponible' => $disponible,
            'stock_actual' => $stockActual,
            'cantidad_requerida' => $validated['cantidad'],
        ]);
    }

    /**
     * Obtener inventario de un producto
     */
    public function inventarioProducto(Request $request, int $productoId)
    {
        $sucursalId = $request->user()->sucursal_id;

        if (!$sucursalId) {
            return response()->json([
                'message' => 'Usuario no tiene sucursal asignada'
            ], 400);
        }

        $inventario = Inventario::with(['producto', 'sucursal'])
            ->where('producto_id', $productoId)
            ->where('sucursal_id', $sucursalId)
            ->first();

        if (!$inventario) {
            return response()->json([
                'message' => 'No hay inventario para este producto en tu sucursal'
            ], 404);
        }

        return response()->json($inventario);
    }

    /**
     * Listar inventario completo de la sucursal
     */
    public function index(Request $request)
    {
        $sucursalId = $request->user()->sucursal_id;

        if (!$sucursalId) {
            return response()->json([
                'message' => 'Usuario no tiene sucursal asignada'
            ], 400);
        }

        $query = Inventario::with(['producto.categoria'])
            ->porSucursal($sucursalId);

        // Filtros
        if ($request->filled('con_stock')) {
            $query->conStock();
        }

        if ($request->filled('sin_stock')) {
            $query->sinStock();
        }

        if ($request->filled('bajo_stock')) {
            $query->bajoStock();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('producto', function($q) use ($search) {
                $q->where('nombre', 'ILIKE', "%{$search}%")
                  ->orWhere('codigo_referencia', 'ILIKE', "%{$search}%");
            });
        }

        $inventarios = $query->get();

        return response()->json([
            'data' => $inventarios
        ]);
    }
}