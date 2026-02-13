<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mandato extends Model
{
    protected $table = 'mandatos';

    protected $fillable = [
        'detalle_venta_id',
        'tipo_documento_id',
        'numero_documento',
        'dv',
        'razon_social',
        'nombres',
    ];

    protected $casts = [
        'dv' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function detalleVenta(): BelongsTo
    {
        return $this->belongsTo(DetalleVenta::class);
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumentoIdentidad::class);
    }

    // Accessors
    public function getDocumentoCompletoAttribute(): string
    {
        if ($this->dv) {
            return "{$this->numero_documento}-{$this->dv}";
        }
        return $this->numero_documento;
    }

    public function getNombreCompletoAttribute(): string
    {
        return $this->razon_social ?? $this->nombres;
    }
}