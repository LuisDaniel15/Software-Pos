<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoOrganizacion extends Model
{
    protected $table = 'tipos_organizacion';

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

    // Scopes
    public function scopePersonaNatural($query)
    {
        return $query->where('id', 2);
    }

    public function scopePersonaJuridica($query)
    {
        return $query->where('id', 1);
    }

    // Accessors
    public function getEsPersonaNaturalAttribute(): bool
    {
        return $this->id === 2;
    }

    public function getEsPersonaJuridicaAttribute(): bool
    {
        return $this->id === 1;
    }
}