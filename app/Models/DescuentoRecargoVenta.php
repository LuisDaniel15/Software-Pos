<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DescuentoRecargoVenta extends Model
{
    protected $table = 'descuentos_recargos_venta';

    protected $fillable = [
        'venta_id',
        'codigo_concepto',
        'es_recargo',
        'razon',
        'base',
        'porcentaje',
        'monto',
    ];

    protected $casts = [
        'es_recargo' => 'boolean',
        'base' => 'decimal:2',
        'porcentaje' => 'decimal:2',
        'monto' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    // Scopes
    public function scopePorVenta($query, int $ventaId)
    {
        return $query->where('venta_id', $ventaId);
    }

    public function scopeDescuentos($query)
    {
        return $query->where('es_recargo', false);
    }

    public function scopeRecargos($query)
    {
        return $query->where('es_recargo', true);
    }

    // Accessors
    public function getEsDescuentoAttribute(): bool
    {
        return !$this->es_recargo;
    }
}