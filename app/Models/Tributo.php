<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tributo extends Model
{
    protected $table = 'tributos';

    protected $fillable = [
        'codigo_dian',
        'nombre',
        'descripcion',
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
        return $query->where('nombre', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('codigo_dian', 'ILIKE', "%{$busqueda}%");
    }

    public function scopePorCodigo($query, string $codigo)
    {
        return $query->where('codigo_dian', $codigo);
    }

    // Accessors
    public function getEsIvaAttribute(): bool
    {
        return $this->codigo_dian === '01';
    }

    public function getEsRetencionAttribute(): bool
    {
        return in_array($this->codigo_dian, ['05', '06', '07']);
    }
}