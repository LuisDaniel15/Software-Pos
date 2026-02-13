<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Http\Request;
use Exception;

class VentaController extends Controller
{
    protected VentaService $ventaService;

    public function __construct(VentaService $ventaService)
    {
        $this->ventaService = $ventaService;
    }

    /**
     * Listar ventas
     */
    public function index(Request $request)
    {
        $query = Venta::with(['cliente', 'usuario', 'tipoDocumento'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('estado_dian')) {
            $query->where('estado_dian', $request->estado_dian);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('desde')) {
            $query->whereDate('fecha_venta', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha_venta', '<=', $request->hasta);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('numero_venta', 'ILIKE', "%{$request->search}%")
                  ->orWhere('numero_factura_dian', 'ILIKE', "%{$request->search}%")
                  ->orWhere('reference_code', 'ILIKE', "%{$request->search}%");
            });
        }

        $ventas = $query->paginate($request->per_page ?? 20);

        return response()->json($ventas);
    }

    /**
     * Crear venta
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'turno_caja_id' => 'nullable|exists:turnos_caja,id',
                'establecimiento_id' => 'nullable|exists:establecimientos,id',
                'forma_pago_id' => 'required|exists:formas_pago,id',
                'metodo_pago_id' => 'required|exists:metodos_pago,id',
                'tipo_operacion_id' => 'required|exists:tipos_operacion,id',
                'fecha_vencimiento' => 'nullable|date',
                'observaciones' => 'nullable|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.producto_id' => 'required|exists:productos,id',
                'items.*.cantidad' => 'required|integer|min:1',
                'items.*.porcentaje_descuento' => 'nullable|numeric|min:0|max:100',
                'items.*.nota' => 'nullable|string|max:250',
                'items.*.retenciones' => 'nullable|array',
                'items.*.retenciones.*.codigo' => 'required_with:items.*.retenciones|string',
                'items.*.retenciones.*.nombre' => 'required_with:items.*.retenciones|string',
                'items.*.retenciones.*.porcentaje' => 'required_with:items.*.retenciones|numeric',
                'descuentos_recargos' => 'nullable|array',
                'descuentos_recargos.*.codigo_concepto' => 'required_with:descuentos_recargos|string',
                'descuentos_recargos.*.es_recargo' => 'required_with:descuentos_recargos|boolean',
                'descuentos_recargos.*.razon' => 'required_with:descuentos_recargos|string',
                'descuentos_recargos.*.base' => 'required_with:descuentos_recargos|numeric',
                'descuentos_recargos.*.porcentaje' => 'nullable|numeric',
                'descuentos_recargos.*.monto' => 'required_with:descuentos_recargos|numeric',
            ]);

            $validated['usuario_id'] = $request->user()->id;
            $validated['facturar_electronica'] = $request->facturar_electronica ?? true;

            $venta = $this->ventaService->crearVenta($validated);

            return response()->json([
                'message' => 'Venta creada exitosamente',
                'data' => $venta->load(['detalles', 'cliente', 'usuario']),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al crear la venta',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ver detalle de venta
     */
    public function show(int $id)
    {
        $venta = Venta::with([
            'cliente.tipoDocumento',
            'cliente.municipio',
            'usuario',
            'establecimiento',
            'tipoDocumento',
            'formaPago',
            'metodoPago',
            'tipoOperacion',
            'detalles.producto',
            'detalles.unidadMedida',
            'detalles.tributo',
            'detalles.retenciones',
            'retenciones',
            'descuentosRecargos',
            'documentosRelacionados',
        ])->findOrFail($id);

        return response()->json($venta);
    }

    /**
     * Reintentar facturación
     */
    public function reintentarFacturacion(int $id)
    {
        try {
            $venta = Venta::findOrFail($id);

            $ventaActualizada = $this->ventaService->reintentarFacturacion($venta);

            return response()->json([
                'message' => 'Facturación reintentada exitosamente',
                'data' => $ventaActualizada,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al reintentar facturación',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener ventas del día
     */
    public function ventasDelDia(Request $request)
    {
        $ventas = Venta::delDia()
            ->with(['cliente', 'usuario'])
            ->orderBy('created_at', 'desc')
            ->get();

        $resumen = [
            'total_ventas' => $ventas->where('estado', 'completada')->count(),
            'total_monto' => $ventas->where('estado', 'completada')->sum('total'),
            'total_pendientes' => $ventas->where('estado_dian', 'pendiente')->count(),
            'total_rechazadas' => $ventas->where('estado_dian', 'rechazada')->count(),
        ];

        return response()->json([
            'ventas' => $ventas,
            'resumen' => $resumen,
        ]);
    }
}