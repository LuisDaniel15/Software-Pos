<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    protected $table = 'cajas';

    protected $fillable = [
        'codigo',
        'nombre',
        'establecimiento_id',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(TurnoCaja::class);
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopeBuscar($query, string $busqueda)
    {
        return $query->where('nombre', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('codigo', 'ILIKE', "%{$busqueda}%");
    }

    // Accessors
    public function getTurnoActivoAttribute(): ?TurnoCaja
    {
        return $this->turnos()
                    ->where('estado', 'abierto')
                    ->latest()
                    ->first();
    }

    public function getTieneturnoAbiertoAttribute(): bool
    {
        return $this->turno_activo !== null;
    }

    // Métodos
    public function puedeAbrirTurno(): bool
    {
        return !$this->tiene_turno_abierto && $this->activa;
    }
}