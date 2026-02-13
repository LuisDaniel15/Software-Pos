<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TurnoCaja extends Model
{
    protected $table = 'turnos_caja';

    protected $fillable = [
        'caja_id',
        'usuario_id',
        'fecha_apertura',
        'fecha_cierre',
        'monto_apertura',
        'monto_cierre',
        'monto_esperado',
        'diferencia',
        'observaciones_apertura',
        'observaciones_cierre',
        'estado',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'monto_apertura' => 'decimal:2',
        'monto_cierre' => 'decimal:2',
        'monto_esperado' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    // Scopes
    public function scopeAbiertos($query)
    {
        return $query->where('estado', 'abierto');
    }

    public function scopeCerrados($query)
    {
        return $query->where('estado', 'cerrado');
    }

    public function scopePorUsuario($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopePorCaja($query, int $cajaId)
    {
        return $query->where('caja_id', $cajaId);
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_apertura', [$desde, $hasta]);
    }

    // Accessors
    public function getEstaAbiertoAttribute(): bool
    {
        return $this->estado === 'abierto';
    }

    public function getEstaCerradoAttribute(): bool
    {
        return $this->estado === 'cerrado';
    }

    public function getTotalVentasAttribute(): float
    {
        return $this->ventas()
                    ->where('estado', 'completada')
                    ->sum('total') ?? 0;
    }

    public function getTotalIngresosAttribute(): float
    {
        return $this->movimientos()
                    ->where('tipo', 'ingreso')
                    ->sum('monto') ?? 0;
    }

    public function getTotalEgresosAttribute(): float
    {
        return $this->movimientos()
                    ->where('tipo', 'egreso')
                    ->sum('monto') ?? 0;
    }

    public function getDuracionAttribute(): ?string
    {
        if (!$this->fecha_cierre) {
            $duracion = now()->diff($this->fecha_apertura);
        } else {
            $duracion = $this->fecha_cierre->diff($this->fecha_apertura);
        }
        
        return sprintf('%d horas, %d minutos', 
            $duracion->h + ($duracion->days * 24), 
            $duracion->i
        );
    }

    // Métodos
    public function calcularMontoEsperado(): float
    {
        return $this->monto_apertura + $this->total_ventas + $this->total_ingresos - $this->total_egresos;
    }

    public function cerrarTurno(float $montoCierre, ?string $observaciones = null): bool
    {
        $this->monto_cierre = $montoCierre;
        $this->monto_esperado = $this->calcularMontoEsperado();
        $this->diferencia = $montoCierre - $this->monto_esperado;
        $this->fecha_cierre = now();
        $this->observaciones_cierre = $observaciones;
        $this->estado = 'cerrado';
        
        return $this->save();
    }
}