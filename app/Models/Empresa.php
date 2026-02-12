<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $table = 'empresa';

    protected $fillable = [
        'nit',
        'dv',
        'razon_social',
        'nombre_comercial',
        'codigo_registro',
        'actividad_economica',
        'telefono',
        'email',
        'direccion',
        'municipio_id',
        'logo',
        'url_logo',
        'activa',
    ];

    protected $casts = [
        'dv' => 'integer',
        'activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function establecimientos(): HasMany
    {
        return $this->hasMany(Establecimiento::class);
    }

    // Scopes
    public function scopeActiva($query)
    {
        return $query->where('activa', true);
    }

    // Accessors
    public function getNitCompletoAttribute(): string
    {
        return "{$this->nit}-{$this->dv}";
    }

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
            'url_logo' => $this->url_logo,
            'nit' => $this->nit,
            'dv' => $this->dv,
            'company' => $this->razon_social,
            'name' => $this->nombre_comercial ?? $this->razon_social,
            'graphic_representation_name' => $this->razon_social,
            'registration_code' => $this->codigo_registro,
            'economic_activity' => $this->actividad_economica,
            'phone' => $this->telefono,
            'email' => $this->email,
            'direction' => $this->direccion,
            'municipality' => $this->municipio ? $this->municipio->nombre : null,
        ];
    }
}