<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TributoCliente extends Model
{
    protected $table = 'tributos_cliente';

    protected $fillable = [
        'codigo',
        'nombre',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
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

    public function scopeIva($query)
    {
        return $query->where('codigo', '01');
    }

    public function scopeNoAplica($query)
    {
        return $query->where('codigo', 'ZZ');
    }

    // Accessors
    public function getEsIvaAttribute(): bool
    {
        return $this->codigo === '01';
    }

    public function getEsNoAplicaAttribute(): bool
    {
        return strtoupper($this->codigo) === 'ZZ';
    }
}