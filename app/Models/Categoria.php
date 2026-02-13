<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopeBuscar($query, string $busqueda)
    {
        return $query->where('nombre', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('descripcion', 'ILIKE', "%{$busqueda}%");
    }

    // Accessors
    public function getCantidadProductosAttribute(): int
    {
        return $this->productos()->count();
    }

    public function getCantidadProductosActivosAttribute(): int
    {
        return $this->productos()->where('activo', true)->count();
    }
}