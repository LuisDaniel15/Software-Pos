<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetencionVenta extends Model
{
    protected $table = 'retenciones_venta';

    protected $fillable = [
        'venta_id',
        'codigo_tributo',
        'nombre_retencion',
        'valor_total',
    ];

    protected $casts = [
        'valor_total' => 'decimal:2',
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

    public function scopeReteIva($query)
    {
        return $query->where('codigo_tributo', '05');
    }

    public function scopeReteRenta($query)
    {
        return $query->where('codigo_tributo', '06');
    }

    // Accessors
    public function getEsReteIvaAttribute(): bool
    {
        return $this->codigo_tributo === '05';
    }

    public function getEsReteRentaAttribute(): bool
    {
        return $this->codigo_tributo === '06';
    }
}