<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TurnoCaja;
use App\Models\Caja;
use App\Services\CajaService;
use Illuminate\Http\Request;
use Exception;

class CajaController extends Controller
{
    protected CajaService $cajaService;

    public function __construct(CajaService $cajaService)
    {
        $this->cajaService = $cajaService;
    }

    /**
     * Listar cajas disponibles para abrir turno
     */
    public function cajasDisponibles()
    {
        $cajas = $this->cajaService->obtenerCajasDisponibles();

        return response()->json($cajas);
    }

    /**
     * Obtener turno activo del usuario
     */
    public function turnoActivo(Request $request)
    {
        $turno = $this->cajaService->obtenerTurnoActivo($request->user()->id);

        if (!$turno) {
            return response()->json([
                'message' => 'No tienes turno abierto',
                'turno' => null,
            ]);
        }

        return response()->json([
            'turno' => $turno,
        ]);
    }

    /**
     * Abrir turno de caja
     */
    public function abrirTurno(Request $request)
    {
        try {
            $validated = $request->validate([
                'caja_id' => 'required|exists:cajas,id',
                'monto_apertura' => 'required|numeric|min:0',
                'observaciones' => 'nullable|string|max:500',
            ]);

            $turno = $this->cajaService->abrirTurno(
                $validated['caja_id'],
                $request->user()->id,
                $validated['monto_apertura'],
                $validated['observaciones'] ?? null
            );

            return response()->json([
                'message' => 'Turno abierto exitosamente',
                'data' => $turno,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al abrir turno',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cerrar turno de caja
     */
    public function cerrarTurno(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'monto_cierre' => 'required|numeric|min:0',
                'observaciones' => 'nullable|string|max:500',
            ]);

            $turno = $this->cajaService->cerrarTurno(
                $id,
                $validated['monto_cierre'],
                $validated['observaciones'] ?? null
            );

            return response()->json([
                'message' => 'Turno cerrado exitosamente',
                'data' => $turno,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al cerrar turno',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Registrar ingreso de caja
     */
    public function registrarIngreso(Request $request)
    {
        try {
            $validated = $request->validate([
                'turno_caja_id' => 'required|exists:turnos_caja,id',
                'monto' => 'required|numeric|min:0',
                'concepto' => 'required|string|max:255',
                'metodo_pago_id' => 'required|exists:metodos_pago,id',
                'observacion' => 'nullable|string|max:500',
            ]);

            $movimiento = $this->cajaService->registrarIngreso(
                $validated['turno_caja_id'],
                $validated['monto'],
                $validated['concepto'],
                $validated['metodo_pago_id'],
                $request->user()->id,
                $validated['observacion'] ?? null
            );

            return response()->json([
                'message' => 'Ingreso registrado exitosamente',
                'data' => $movimiento,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al registrar ingreso',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Registrar egreso de caja
     */
    public function registrarEgreso(Request $request)
    {
        try {
            $validated = $request->validate([
                'turno_caja_id' => 'required|exists:turnos_caja,id',
                'monto' => 'required|numeric|min:0',
                'concepto' => 'required|string|max:255',
                'metodo_pago_id' => 'required|exists:metodos_pago,id',
                'observacion' => 'nullable|string|max:500',
            ]);

            $movimiento = $this->cajaService->registrarEgreso(
                $validated['turno_caja_id'],
                $validated['monto'],
                $validated['concepto'],
                $validated['metodo_pago_id'],
                $request->user()->id,
                $validated['observacion'] ?? null
            );

            return response()->json([
                'message' => 'Egreso registrado exitosamente',
                'data' => $movimiento,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al registrar egreso',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener resumen del turno
     */
    public function resumenTurno(int $id)
    {
        try {
            $resumen = $this->cajaService->obtenerResumenTurno($id);

            return response()->json($resumen);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener resumen',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Listar turnos del día
     */
    public function turnosDelDia(Request $request)
    {
        $turnos = $this->cajaService->obtenerTurnosDelDia(
            $request->fecha ?? null
        );

        return response()->json($turnos);
    }

    /**
     * Historial de turnos
     */
    public function historial(Request $request)
    {
        $query = TurnoCaja::with(['caja', 'usuario'])
            ->orderBy('fecha_apertura', 'desc');

        // Filtros
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('caja_id')) {
            $query->where('caja_id', $request->caja_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('desde')) {
            $query->whereDate('fecha_apertura', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha_apertura', '<=', $request->hasta);
        }

        $turnos = $query->paginate($request->per_page ?? 20);

        return response()->json($turnos);
    }
}