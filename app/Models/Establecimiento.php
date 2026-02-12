<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Establecimiento extends Model
{
    protected $table = 'establecimientos';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email',
        'municipio_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, string $busqueda)
    {
        return $query->where('nombre', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('direccion', 'ILIKE', "%{$busqueda}%");
    }

    // Accessors
    public function getDireccionCompletaAttribute(): string
    {
        return $this->municipio 
            ? "{$this->direccion}, {$this->municipio->nombre_completo}"
            : $this->direccion;
    }

    // Métodos
    public function getDatosParaFactus(): array
    {
        return [
            'name' => $this->nombre,
            'address' => $this->direccion,
            'phone_number' => $this->telefono,
            'email' => $this->email,
            'municipality_id' => $this->municipio_id,
        ];
    }
}