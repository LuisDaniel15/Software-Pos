<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaCredito extends Model
{
    protected $table = 'notas_credito';

    protected $fillable = [
        'venta_original_id',
        'venta_nota_id',
        'motivo',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function ventaOriginal(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_original_id');
    }

    public function ventaNota(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_nota_id');
    }

    // Scopes
    public function scopePorVentaOriginal($query, int $ventaId)
    {
        return $query->where('venta_original_id', $ventaId);
    }
}