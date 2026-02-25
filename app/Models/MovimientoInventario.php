<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'producto_id',
        'sucursal_origen_id',
        'sucursal_destino_id',
        'tipo_movimiento',
        'cantidad',
        'costo_unitario',
        'precio_venta',
        'motivo',
        'referencia',
        'fecha_movimiento',
        'usuario_id',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'fecha_movimiento' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =====================================
    // RELACIONES
    // =====================================

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function sucursalOrigen(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_origen_id');
    }

    public function sucursalDestino(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // =====================================
    // SCOPES
    // =====================================

    public function scopePorSucursal(Builder $query, int $sucursalId): Builder
    {
        return $query->where(function($q) use ($sucursalId) {
            $q->where('sucursal_origen_id', $sucursalId)
              ->orWhere('sucursal_destino_id', $sucursalId);
        });
    }

    public function scopePorProducto(Builder $query, int $productoId): Builder
    {
        return $query->where('producto_id', $productoId);
    }

    public function scopeEntradas(Builder $query): Builder
    {
        return $query->whereIn('tipo_movimiento', ['entrada', 'traslado_entrada']);
    }

    public function scopeSalidas(Builder $query): Builder
    {
        return $query->whereIn('tipo_movimiento', ['salida', 'traslado_salida']);
    }

    public function scopeAjustes(Builder $query): Builder
    {
        return $query->where('tipo_movimiento', 'ajuste');
    }

    public function scopeTraslados(Builder $query): Builder
    {
        return $query->whereIn('tipo_movimiento', ['traslado_salida', 'traslado_entrada']);
    }

    public function scopePorFecha(Builder $query, $desde, $hasta): Builder
    {
        return $query->whereBetween('fecha_movimiento', [$desde, $hasta]);
    }

    // =====================================
    // ACCESSORS
    // =====================================

    public function getEsEntradaAttribute(): bool
    {
        return in_array($this->tipo_movimiento, ['entrada', 'traslado_entrada']);
    }

    public function getEsSalidaAttribute(): bool
    {
        return in_array($this->tipo_movimiento, ['salida', 'traslado_salida']);
    }

    public function getEsAjusteAttribute(): bool
    {
        return $this->tipo_movimiento === 'ajuste';
    }

    public function getEsTrasladoAttribute(): bool
    {
        return in_array($this->tipo_movimiento, ['traslado_salida', 'traslado_entrada']);
    }

    public function getValorTotalAttribute(): float
    {
        $costo = $this->costo_unitario ?? $this->precio_venta ?? 0;
        return round($this->cantidad * $costo, 2);
    }

    // =====================================
    // MÉTODOS
    // =====================================

    public function getDescripcionMovimiento(): string
    {
        $descripciones = [
            'entrada' => 'Entrada de mercancía',
            'salida' => 'Salida de mercancía',
            'ajuste' => 'Ajuste de inventario',
            'traslado_salida' => 'Traslado a otra sucursal',
            'traslado_entrada' => 'Traslado desde otra sucursal',
        ];

        return $descripciones[$this->tipo_movimiento] ?? 'Movimiento';
    }

    public static function obtenerKardex(int $productoId, int $sucursalId, ?string $desde = null, ?string $hasta = null): array
    {
        $query = static::where('producto_id', $productoId)
            ->where(function($q) use ($sucursalId) {
                $q->where('sucursal_origen_id', $sucursalId)
                  ->orWhere('sucursal_destino_id', $sucursalId);
            })
            ->with(['usuario', 'sucursalOrigen', 'sucursalDestino'])
            ->orderBy('fecha_movimiento', 'asc')
            ->orderBy('id', 'asc');

        if ($desde) {
            $query->where('fecha_movimiento', '>=', $desde);
        }

        if ($hasta) {
            $query->where('fecha_movimiento', '<=', $hasta);
        }

        $movimientos = $query->get();

        $kardex = [];
        $saldo = 0;

        foreach ($movimientos as $mov) {
            $entradas = 0;
            $salidas = 0;

            if ($mov->sucursal_origen_id == $sucursalId) {
                if ($mov->es_entrada) {
                    $entradas = $mov->cantidad;
                    $saldo += $mov->cantidad;
                } else {
                    $salidas = $mov->cantidad;
                    $saldo -= $mov->cantidad;
                }
            } elseif ($mov->sucursal_destino_id == $sucursalId) {
                $entradas = $mov->cantidad;
                $saldo += $mov->cantidad;
            }

            $kardex[] = [
                'fecha' => $mov->fecha_movimiento->format('Y-m-d H:i:s'),
                'tipo_movimiento' => $mov->getDescripcionMovimiento(),
                'referencia' => $mov->referencia ?? '-',
                'entradas' => $entradas,
                'salidas' => $salidas,
                'saldo' => $saldo,
                'costo_unitario' => $mov->costo_unitario,
                'costo_total' => $mov->valor_total,
                'usuario' => $mov->usuario?->nombre,
                'motivo' => $mov->motivo,
                'sucursal_origen' => $mov->sucursalOrigen?->nombre,
                'sucursal_destino' => $mov->sucursalDestino?->nombre,
            ];
        }

        return $kardex;
    }
}