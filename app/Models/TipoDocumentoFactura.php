<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoDocumentoFactura extends Model
{
    protected $table = 'tipos_documento_factura';

    protected $fillable = [
        'codigo',
        'descripcion',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    // Scopes
    public function scopeFactura($query)
    {
        return $query->where('codigo', '01');
    }

    public function scopeNotaCredito($query)
    {
        return $query->where('codigo', '03');
    }

    // Accessors
    public function getEsFacturaAttribute(): bool
    {
        return $this->codigo === '01';
    }

    public function getEsNotaCreditoAttribute(): bool
    {
        return $this->codigo === '03';
    }
}