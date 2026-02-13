<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\ProductoService;
use Illuminate\Http\Request;
use Exception;

class ProductoController extends Controller
{
    protected ProductoService $productoService;

    public function __construct(ProductoService $productoService)
    {
        $this->productoService = $productoService;
    }

    /**
     * Listar productos
     */
    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'unidadMedida', 'tributo'])
            ->orderBy('nombre');

        // Filtros
        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('search')) {
            $query->buscar($request->search);
        }

        if ($request->boolean('solo_con_stock')) {
            $query->conStock();
        }

        if ($request->boolean('solo_bajo_stock')) {
            $query->bajoStock();
        }

        $productos = $query->paginate($request->per_page ?? 50);

        return response()->json($productos);
    }

    /**
     * Crear producto
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'categoria_id' => 'nullable|exists:categorias,id',
                'codigo_referencia' => 'required|string|max:100|unique:productos',
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio_venta' => 'required|numeric|min:0',
                'porcentaje_iva' => 'required|string',
                'costo_compra' => 'nullable|numeric|min:0',
                'stock_actual' => 'nullable|numeric|min:0',
                'stock_minimo' => 'nullable|numeric|min:0',
                'unidad_medida_id' => 'required|exists:unidades_medida,id',
                'codigo_estandar_id' => 'required|exists:codigos_estandar,id',
                'codigo_estandar_valor' => 'nullable|string|max:100',
                'tributo_id' => 'required|exists:tributos,id',
                'es_excluido' => 'required|integer|in:0,1',
                'permite_mandato' => 'nullable|boolean',
            ]);

            $producto = $this->productoService->crearProducto($validated);

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'data' => $producto,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al crear el producto',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ver producto
     */
    public function show(int $id)
    {
        $producto = Producto::with([
            'categoria',
            'unidadMedida',
            'codigoEstandar',
            'tributo'
        ])->findOrFail($id);

        return response()->json($producto);
    }

    /**
     * Actualizar producto
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'categoria_id' => 'nullable|exists:categorias,id',
                'codigo_referencia' => 'required|string|max:100',
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio_venta' => 'required|numeric|min:0',
                'porcentaje_iva' => 'required|string',
                'costo_compra' => 'nullable|numeric|min:0',
                'stock_minimo' => 'nullable|numeric|min:0',
                'unidad_medida_id' => 'required|exists:unidades_medida,id',
                'codigo_estandar_id' => 'required|exists:codigos_estandar,id',
                'codigo_estandar_valor' => 'nullable|string|max:100',
                'tributo_id' => 'required|exists:tributos,id',
                'es_excluido' => 'required|integer|in:0,1',
                'permite_mandato' => 'nullable|boolean',
                'activo' => 'nullable|boolean',
            ]);

            $producto = $this->productoService->actualizarProducto($id, $validated);

            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'data' => $producto,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el producto',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Buscar productos
     */
    public function buscar(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $productos = $this->productoService->buscarProductos(
            $request->q,
            $request->limit ?? 20,
            $request->boolean('solo_activos', true)
        );

        return response()->json($productos);
    }

    /**
     * Productos con stock bajo
     */
    public function bajoStock()
    {
        $productos = $this->productoService->obtenerProductosBajoStock();

        return response()->json($productos);
    }
}