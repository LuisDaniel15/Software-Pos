<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoDocumentoIdentidad extends Model
{
    protected $table = 'tipos_documento_identidad';

    protected $fillable = [
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

    public function mandatos(): HasMany
    {
        return $this->hasMany(Mandato::class);
    }

    // Scopes
    public function scopeBuscar($query, string $busqueda)
    {
        return $query->where('nombre', 'ILIKE', "%{$busqueda}%");
    }

    // Accessors
    public function getEsNitAttribute(): bool
    {
        return $this->id === 6;
    }

    public function getRequiereDvAttribute(): bool
    {
        return $this->id === 6; // Solo NIT requiere DV
    }
}