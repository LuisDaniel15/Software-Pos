<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodigoEstandar extends Model
{
    protected $table = 'codigos_estandar';

    protected $fillable = [
        'nombre',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    // Scopes
    public function scopeBuscar($query, string $busqueda)
    {
        return $query->where('nombre', 'ILIKE', "%{$busqueda}%");
    }

    // Accessors
    public function getEsGtinAttribute(): bool
    {
        return $this->id === 4;
    }

    public function getEsUnspscAttribute(): bool
    {
        return $this->id === 2;
    }
}