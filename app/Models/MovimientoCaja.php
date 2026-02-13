<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCaja extends Model
{
    protected $table = 'movimientos_caja';

    public $timestamps = false;

    protected $fillable = [
        'turno_caja_id',
        'tipo',
        'concepto',
        'monto',
        'metodo_pago_id',
        'venta_id',
        'usuario_id',
        'observacion',
        'created_at',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Relaciones
    public function turnoCaja(): BelongsTo
    {
        return $this->belongsTo(TurnoCaja::class);
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'ingreso');
    }

    public function scopeEgresos($query)
    {
        return $query->where('tipo', 'egreso');
    }

    public function scopePorTurno($query, int $turnoId)
    {
        return $query->where('turno_caja_id', $turnoId);
    }

    public function scopeDelDia($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Accessors
    public function getEsIngresoAttribute(): bool
    {
        return $this->tipo === 'ingreso';
    }

    public function getEsEgresoAttribute(): bool
    {
        return $this->tipo === 'egreso';
    }

    public function getEsDeVentaAttribute(): bool
    {
        return $this->venta_id !== null;
    }

    // Métodos
    public static function registrarMovimientoVenta(
        int $turnoCajaId,
        int $ventaId,
        float $monto,
        int $metodoPagoId,
        int $usuarioId
    ): self {
        return self::create([
            'turno_caja_id' => $turnoCajaId,
            'tipo' => 'ingreso',
            'concepto' => 'Venta',
            'monto' => $monto,
            'metodo_pago_id' => $metodoPagoId,
            'venta_id' => $ventaId,
            'usuario_id' => $usuarioId,
            'created_at' => now(),
        ]);
    }
}