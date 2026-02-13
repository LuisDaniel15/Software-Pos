<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kardex extends Model
{
    protected $table = 'kardex';

    public $timestamps = false;

    protected $fillable = [
        'producto_id',
        'tipo_movimiento',
        'referencia_tipo',
        'referencia_id',
        'cantidad',
        'stock_anterior',
        'stock_nuevo',
        'costo_unitario',
        'usuario_id',
        'observacion',
        'created_at',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'stock_anterior' => 'decimal:2',
        'stock_nuevo' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Relaciones
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePorProducto($query, int $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    public function scopePorTipoMovimiento($query, string $tipo)
    {
        return $query->where('tipo_movimiento', $tipo);
    }

    public function scopeEntradas($query)
    {
        return $query->where('tipo_movimiento', 'entrada');
    }

    public function scopeSalidas($query)
    {
        return $query->where('tipo_movimiento', 'salida');
    }

    public function scopeAjustes($query)
    {
        return $query->where('tipo_movimiento', 'ajuste');
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('created_at', [$desde, $hasta]);
    }

    public function scopeRecientes($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    // Accessors
    public function getValorTotalAttribute(): ?float
    {
        if (!$this->costo_unitario) {
            return null;
        }
        
        return $this->cantidad * $this->costo_unitario;
    }

    public function getEsEntradaAttribute(): bool
    {
        return $this->tipo_movimiento === 'entrada';
    }

    public function getEsSalidaAttribute(): bool
    {
        return $this->tipo_movimiento === 'salida';
    }

    public function getEsAjusteAttribute(): bool
    {
        return $this->tipo_movimiento === 'ajuste';
    }

    // Métodos
    public static function registrarMovimiento(
        int $productoId,
        string $tipoMovimiento,
        string $referenciaTipo,
        ?int $referenciaId,
        int $cantidad,
        float $stockAnterior,
        ?float $costoUnitario,
        int $usuarioId,
        ?string $observacion = null
    ): self {
        $stockNuevo = $stockAnterior;
        
        if ($tipoMovimiento === 'entrada') {
            $stockNuevo += $cantidad;
        } elseif ($tipoMovimiento === 'salida') {
            $stockNuevo -= $cantidad;
        } else { // ajuste
            $stockNuevo = $cantidad;
        }

        return self::create([
            'producto_id' => $productoId,
            'tipo_movimiento' => $tipoMovimiento,
            'referencia_tipo' => $referenciaTipo,
            'referencia_id' => $referenciaId,
            'cantidad' => $cantidad,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
            'costo_unitario' => $costoUnitario,
            'usuario_id' => $usuarioId,
            'observacion' => $observacion,
            'created_at' => now(),
        ]);
    }
}