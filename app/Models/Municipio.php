<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    protected $table = 'municipios';

    protected $fillable = [
        'codigo_dian',
        'nombre',
        'departamento',
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

    public function empresas(): HasMany
    {
        return $this->hasMany(Empresa::class);
    }

    public function establecimientos(): HasMany
    {
        return $this->hasMany(Establecimiento::class);
    }

    // Scopes
    public function scopePorDepartamento($query, string $departamento)
    {
        return $query->where('departamento', $departamento);
    }

    public function scopeBuscar($query, string $busqueda)
    {
        return $query->where('nombre', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('codigo_dian', 'ILIKE', "%{$busqueda}%");
    }

    // Accessors
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre}, {$this->departamento}";
    }
}