<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetencionDetalleVenta extends Model
{
    protected $table = 'retenciones_detalle_venta';

    protected $fillable = [
        'detalle_venta_id',
        'codigo_retencion',
        'nombre_retencion',
        'porcentaje',
        'valor',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'valor' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function detalleVenta(): BelongsTo
    {
        return $this->belongsTo(DetalleVenta::class);
    }

    // Scopes
    public function scopePorDetalle($query, int $detalleVentaId)
    {
        return $query->where('detalle_venta_id', $detalleVentaId);
    }

    public function scopeReteIva($query)
    {
        return $query->where('codigo_retencion', '05');
    }

    public function scopeReteRenta($query)
    {
        return $query->where('codigo_retencion', '06');
    }

    // Accessors
    public function getEsReteIvaAttribute(): bool
    {
        return $this->codigo_retencion === '05';
    }

    public function getEsReteRentaAttribute(): bool
    {
        return $this->codigo_retencion === '06';
    }
}