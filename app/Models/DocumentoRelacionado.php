<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoRelacionado extends Model
{
    protected $table = 'documentos_relacionados';

    protected $fillable = [
        'venta_id',
        'codigo_documento',
        'numero_documento',
        'fecha_emision',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
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
}