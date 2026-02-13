<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\TurnoCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    /**
     * Dashboard principal
     */
    public function dashboard(Request $request)
    {
        $hoy = today();
        $inicioMes = now()->startOfMonth();
        $finMes = now()->endOfMonth();

        // Ventas del día
        $ventasHoy = Venta::delDia()
            ->where('estado', 'completada')
            ->get();

        // Ventas del mes
        $ventasMes = Venta::whereBetween('fecha_venta', [$inicioMes, $finMes])
            ->where('estado', 'completada')
            ->get();

        // Productos bajo stock
        $productosBajoStock = Producto::bajoStock()
            ->activos()
            ->count();

        // Facturas pendientes
        $facturasPendientes = Venta::pendientesFacturacion()
            ->count();

        // Facturas rechazadas
        $facturasRechazadas = Venta::rechazadas()
            ->count();

        return response()->json([
            'ventas_hoy' => [
                'cantidad' => $ventasHoy->count(),
                'total' => $ventasHoy->sum('total'),
            ],
            'ventas_mes' => [
                'cantidad' => $ventasMes->count(),
                'total' => $ventasMes->sum('total'),
            ],
            'productos_bajo_stock' => $productosBajoStock,
            'facturas_pendientes' => $facturasPendientes,
            'facturas_rechazadas' => $facturasRechazadas,
        ]);
    }

    /**
     * Reporte de ventas por período
     */
    public function ventasPorPeriodo(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $ventas = Venta::whereBetween('fecha_venta', [$request->desde, $request->hasta])
            ->where('estado', 'completada')
            ->with(['cliente', 'usuario'])
            ->get();

        $resumen = [
            'total_ventas' => $ventas->count(),
            'total_ingresos' => $ventas->sum('total'),
            'total_iva' => $ventas->sum('total_iva'),
            'total_descuentos' => $ventas->sum('total_descuentos'),
            'total_recargos' => $ventas->sum('total_recargos'),
            'ticket_promedio' => $ventas->count() > 0 
                ? $ventas->sum('total') / $ventas->count() 
                : 0,
        ];

        // Ventas por día
        $ventasPorDia = $ventas->groupBy(function($venta) {
            return $venta->fecha_venta->format('Y-m-d');
        })->map(function($ventasDia) {
            return [
                'cantidad' => $ventasDia->count(),
                'total' => $ventasDia->sum('total'),
            ];
        });

        return response()->json([
            'resumen' => $resumen,
            'ventas' => $ventas,
            'ventas_por_dia' => $ventasPorDia,
        ]);
    }

    /**
     * Reporte de ventas por usuario
     */
    public function ventasPorUsuario(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $ventas = DB::table('ventas')
            ->join('users', 'ventas.usuario_id', '=', 'users.id')
            ->whereBetween('ventas.fecha_venta', [$request->desde, $request->hasta])
            ->where('ventas.estado', 'completada')
            ->select(
                'users.id',
                'users.nombre',
                DB::raw('COUNT(ventas.id) as total_ventas'),
                DB::raw('SUM(ventas.total) as total_ingresos'),
                DB::raw('AVG(ventas.total) as ticket_promedio')
            )
            ->groupBy('users.id', 'users.nombre')
            ->orderByDesc('total_ingresos')
            ->get();

        return response()->json($ventas);
    }

    /**
     * Reporte de ventas por cliente
     */
    public function ventasPorCliente(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $ventas = DB::table('ventas')
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->whereBetween('ventas.fecha_venta', [$request->desde, $request->hasta])
            ->where('ventas.estado', 'completada')
            ->select(
                'clientes.id',
                'clientes.numero_documento',
                DB::raw("COALESCE(clientes.razon_social, CONCAT(clientes.nombres, ' ', clientes.apellidos)) as nombre_cliente"),
                DB::raw('COUNT(ventas.id) as total_ventas'),
                DB::raw('SUM(ventas.total) as total_compras'),
                DB::raw('AVG(ventas.total) as ticket_promedio')
            )
            ->groupBy('clientes.id', 'clientes.numero_documento', 'nombre_cliente')
            ->orderByDesc('total_compras')
            ->limit($request->limit ?? 20)
            ->get();

        return response()->json($ventas);
    }

    /**
     * Reporte de arqueos de caja
     */
    public function arqueosCaja(Request $request)
    {
        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        $turnos = TurnoCaja::whereBetween('fecha_apertura', [$request->desde, $request->hasta])
            ->with(['caja', 'usuario'])
            ->orderBy('fecha_apertura', 'desc')
            ->get();

        $resumen = [
            'total_turnos' => $turnos->count(),
            'turnos_abiertos' => $turnos->where('estado', 'abierto')->count(),
            'turnos_cerrados' => $turnos->where('estado', 'cerrado')->count(),
            'total_diferencias' => $turnos->where('estado', 'cerrado')->sum('diferencia'),
        ];

        return response()->json([
            'resumen' => $resumen,
            'turnos' => $turnos,
        ]);
    }
}