<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\TurnoCaja;
use App\Models\MovimientoCaja;
use Illuminate\Support\Facades\DB;
use Exception;

class CajaService
{
    /**
     * Abrir turno de caja
     */
    public function abrirTurno(
        int $cajaId,
        int $usuarioId,
        float $montoApertura,
        ?string $observaciones = null
    ): TurnoCaja {
        return DB::transaction(function () use (
            $cajaId,
            $usuarioId,
            $montoApertura,
            $observaciones
        ) {
            $caja = Caja::findOrFail($cajaId);

            // Validar que la caja esté activa
            if (!$caja->activa) {
                throw new Exception('La caja está inactiva');
            }

            // Validar que no haya otro turno abierto
            if ($caja->tiene_turno_abierto) {
                throw new Exception('Ya existe un turno abierto para esta caja');
            }

            // Validar que el usuario no tenga otro turno abierto en otra caja
            $turnoUsuario = TurnoCaja::abiertos()
                ->where('usuario_id', $usuarioId)
                ->first();

            if ($turnoUsuario) {
                throw new Exception(
                    "Ya tienes un turno abierto en la caja {$turnoUsuario->caja->nombre}"
                );
            }

            // Crear turno
            $turno = TurnoCaja::create([
                'caja_id' => $cajaId,
                'usuario_id' => $usuarioId,
                'fecha_apertura' => now(),
                'monto_apertura' => $montoApertura,
                'observaciones_apertura' => $observaciones,
                'estado' => 'abierto',
            ]);

            return $turno->fresh(['caja', 'usuario']);
        });
    }

    /**
     * Cerrar turno de caja
     */
    public function cerrarTurno(
        int $turnoId,
        float $montoCierre,
        ?string $observaciones = null
    ): TurnoCaja {
        return DB::transaction(function () use ($turnoId, $montoCierre, $observaciones) {
            $turno = TurnoCaja::findOrFail($turnoId);

            // Validar que el turno esté abierto
            if ($turno->esta_cerrado) {
                throw new Exception('Este turno ya está cerrado');
            }

            // Cerrar el turno
            $turno->cerrarTurno($montoCierre, $observaciones);

            return $turno->fresh();
        });
    }

    /**
     * Registrar ingreso de caja
     */
    public function registrarIngreso(
        int $turnoId,
        float $monto,
        string $concepto,
        int $metodoPagoId,
        int $usuarioId,
        ?string $observacion = null
    ): MovimientoCaja {
        $turno = TurnoCaja::findOrFail($turnoId);

        if ($turno->esta_cerrado) {
            throw new Exception('No se pueden registrar movimientos en un turno cerrado');
        }

        return MovimientoCaja::create([
            'turno_caja_id' => $turnoId,
            'tipo' => 'ingreso',
            'concepto' => $concepto,
            'monto' => $monto,
            'metodo_pago_id' => $metodoPagoId,
            'usuario_id' => $usuarioId,
            'observacion' => $observacion,
            'created_at' => now(),
        ]);
    }

    /**
     * Registrar egreso de caja
     */
    public function registrarEgreso(
        int $turnoId,
        float $monto,
        string $concepto,
        int $metodoPagoId,
        int $usuarioId,
        ?string $observacion = null
    ): MovimientoCaja {
        $turno = TurnoCaja::findOrFail($turnoId);

        if ($turno->esta_cerrado) {
            throw new Exception('No se pueden registrar movimientos en un turno cerrado');
        }

        return MovimientoCaja::create([
            'turno_caja_id' => $turnoId,
            'tipo' => 'egreso',
            'concepto' => $concepto,
            'monto' => $monto,
            'metodo_pago_id' => $metodoPagoId,
            'usuario_id' => $usuarioId,
            'observacion' => $observacion,
            'created_at' => now(),
        ]);
    }

    /**
     * Obtener resumen del turno
     */
    public function obtenerResumenTurno(int $turnoId): array
    {
        $turno = TurnoCaja::with(['ventas', 'movimientos'])
            ->findOrFail($turnoId);

        $ventasEfectivo = $turno->ventas()
            ->where('estado', 'completada')
            ->whereHas('metodoPago', fn($q) => $q->where('codigo', '10'))
            ->sum('total');

        $ventasTarjeta = $turno->ventas()
            ->where('estado', 'completada')
            ->whereHas('metodoPago', fn($q) => $q->whereIn('codigo', ['48', '49']))
            ->sum('total');

        $ventasOtros = $turno->ventas()
            ->where('estado', 'completada')
            ->whereHas('metodoPago', fn($q) => 
                $q->whereNotIn('codigo', ['10', '48', '49'])
            )
            ->sum('total');

        return [
            'turno' => $turno,
            'monto_apertura' => $turno->monto_apertura,
            'total_ventas' => $turno->total_ventas,
            'total_ingresos' => $turno->total_ingresos,
            'total_egresos' => $turno->total_egresos,
            'monto_esperado' => $turno->calcularMontoEsperado(),
            'monto_cierre' => $turno->monto_cierre,
            'diferencia' => $turno->diferencia,
            'cantidad_ventas' => $turno->ventas()->where('estado', 'completada')->count(),
            'ventas_efectivo' => $ventasEfectivo,
            'ventas_tarjeta' => $ventasTarjeta,
            'ventas_otros' => $ventasOtros,
            'duracion' => $turno->duracion,
        ];
    }

    /**
     * Obtener turnos del día
     */
    public function obtenerTurnosDelDia(?string $fecha = null)
    {
        $fecha = $fecha ?? today();

        return TurnoCaja::whereDate('fecha_apertura', $fecha)
            ->with(['caja', 'usuario'])
            ->orderBy('fecha_apertura', 'desc')
            ->get();
    }

    /**
     * Obtener cajas disponibles para abrir turno
     */
    public function obtenerCajasDisponibles()
    {
        return Caja::activas()
            ->with('establecimiento')
            ->whereDoesntHave('turnos', function ($query) {
                $query->where('estado', 'abierto');
            })
            ->get();
    }

    /**
     * Obtener turno activo del usuario
     */
    public function obtenerTurnoActivo(int $usuarioId): ?TurnoCaja
    {
        return TurnoCaja::abiertos()
            ->where('usuario_id', $usuarioId)
            ->with(['caja', 'ventas', 'movimientos'])
            ->first();
    }
}