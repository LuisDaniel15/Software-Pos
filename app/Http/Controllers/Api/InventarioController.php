<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kardex;
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
     * Registrar entrada de inventario
     */
    public function registrarEntrada(Request $request)
    {
        try {
            $validated = $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'cantidad' => 'required|integer|min:1',
                'referencia_tipo' => 'required|string',
                'referencia_id' => 'nullable|integer',
                'costo_unitario' => 'nullable|numeric|min:0',
                'observacion' => 'nullable|string|max:500',
            ]);

            $kardex = $this->inventarioService->registrarEntrada(
                $validated['producto_id'],
                $validated['cantidad'],
                $validated['referencia_tipo'],
                $validated['referencia_id'] ?? null,
                $request->user()->id,
                $validated['costo_unitario'] ?? null,
                $validated['observacion'] ?? null
            );

            return response()->json([
                'message' => 'Entrada registrada exitosamente',
                'data' => $kardex->load('producto'),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al registrar entrada',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Registrar salida de inventario
     */
    public function registrarSalida(Request $request)
    {
        try {
            $validated = $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'cantidad' => 'required|integer|min:1',
                'referencia_tipo' => 'required|string',
                'referencia_id' => 'nullable|integer',
                'observacion' => 'nullable|string|max:500',
            ]);

            $kardex = $this->inventarioService->registrarSalida(
                $validated['producto_id'],
                $validated['cantidad'],
                $validated['referencia_tipo'],
                $validated['referencia_id'] ?? null,
                $request->user()->id,
                $validated['observacion'] ?? null
            );

            return response()->json([
                'message' => 'Salida registrada exitosamente',
                'data' => $kardex->load('producto'),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al registrar salida',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Registrar ajuste de inventario
     */
    public function registrarAjuste(Request $request)
    {
        try {
            $validated = $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'cantidad_nueva' => 'required|integer|min:0',
                'motivo' => 'required|string|max:500',
            ]);

            $kardex = $this->inventarioService->registrarAjuste(
                $validated['producto_id'],
                $validated['cantidad_nueva'],
                $request->user()->id,
                $validated['motivo']
            );

            return response()->json([
                'message' => 'Ajuste registrado exitosamente',
                'data' => $kardex->load('producto'),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al registrar ajuste',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener kardex de un producto
     */
    public function kardexProducto(int $productoId, Request $request)
    {
        $kardex = $this->inventarioService->obtenerKardexProducto(
            $productoId,
            $request->desde ?? null,
            $request->hasta ?? null
        );

        return response()->json($kardex);
    }

    /**
     * Productos con stock bajo
     */
    public function productosBajoStock()
    {
        $productos = $this->inventarioService->obtenerProductosBajoStock();

        return response()->json($productos);
    }

    /**
     * Valor total del inventario
     */
    public function valorInventario()
    {
        $resumen = $this->inventarioService->calcularValorInventario();

        return response()->json($resumen);
    }

    /**
     * Movimientos del día
     */
    public function movimientosDelDia(Request $request)
    {
        $movimientos = $this->inventarioService->obtenerMovimientosDelDia(
            $request->fecha ?? null
        );

        return response()->json($movimientos);
    }

    /**
     * Productos más vendidos
     */
    public function productosMasVendidos(Request $request)
    {
        $productos = $this->inventarioService->obtenerProductosMasVendidos(
            $request->limit ?? 10,
            $request->desde ?? null,
            $request->hasta ?? null
        );

        return response()->json($productos);
    }
}