<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';

    protected $fillable = [
        'codigo',
        'nombre',
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

    public function movimientosCaja(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class);
    }

    // Scopes
    public function scopeBuscar($query, string $busqueda)
    {
        return $query->where('nombre', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('codigo', 'ILIKE', "%{$busqueda}%");
    }

    public function scopePorCodigo($query, string $codigo)
    {
        return $query->where('codigo', $codigo);
    }

    // Accessors
    public function getEsEfectivoAttribute(): bool
    {
        return $this->codigo === '10';
    }

    public function getEsTarjetaAttribute(): bool
    {
        return in_array($this->codigo, ['48', '49']);
    }
}