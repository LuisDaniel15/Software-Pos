<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'tipo_documento_id',
        'numero_documento',
        'dv',
        'tipo_persona',
        'razon_social',
        'nombre_comercial',
        'nombres',
        'apellidos',
        'graphic_representation_name',
        'direccion',
        'telefono',
        'email',
        'municipio_id',
        'tipo_organizacion_id',
        'tributo_cliente_id',
        'activo',
    ];

    protected $casts = [
        'dv' => 'integer',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumentoIdentidad::class, 'tipo_documento_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function tipoOrganizacion(): BelongsTo
    {
        return $this->belongsTo(TipoOrganizacion::class);
    }

    public function tributoCliente(): BelongsTo
    {
        return $this->belongsTo(TributoCliente::class);
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

    public function scopePersonaNatural($query)
    {
        return $query->where('tipo_persona', 'natural');
    }

    public function scopePersonaJuridica($query)
    {
        return $query->where('tipo_persona', 'juridica');
    }

    public function scopeBuscar($query, string $busqueda)
    {
        return $query->where(function($q) use ($busqueda) {
            $q->where('numero_documento', 'ILIKE', "%{$busqueda}%")
              ->orWhere('razon_social', 'ILIKE', "%{$busqueda}%")
              ->orWhere('nombres', 'ILIKE', "%{$busqueda}%")
              ->orWhere('apellidos', 'ILIKE', "%{$busqueda}%")
              ->orWhere('email', 'ILIKE', "%{$busqueda}%");
        });
    }

    // Accessors
    public function getNombreCompletoAttribute(): string
    {
        if ($this->tipo_persona === 'juridica') {
            return $this->razon_social;
        }
        
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public function getDocumentoCompletoAttribute(): string
    {
        if ($this->dv) {
            return "{$this->numero_documento}-{$this->dv}";
        }
        return $this->numero_documento;
    }

    public function getEsPersonaNaturalAttribute(): bool
    {
        return $this->tipo_persona === 'natural';
    }

    public function getEsPersonaJuridicaAttribute(): bool
    {
        return $this->tipo_persona === 'juridica';
    }

    public function getRequiereDvAttribute(): bool
    {
        return $this->tipoDocumento && $this->tipoDocumento->requiere_dv;
    }

    // Métodos
    public function getDatosParaFactus(): array
    {
        return [
            'identification_document_id' => $this->tipo_documento_id,
            'identification' => $this->numero_documento,
            'dv' => $this->dv,
            'company' => $this->tipo_persona === 'juridica' ? $this->razon_social : null,
            'trade_name' => $this->nombre_comercial,
            'names' => $this->tipo_persona === 'natural' ? $this->nombres : null,
            'graphic_representation_name' => $this->graphic_representation_name ?? $this->nombre_completo,
            'address' => $this->direccion,
            'email' => $this->email,
            'phone' => $this->telefono,
            'legal_organization_id' => $this->tipo_organizacion_id,
            'tribute_id' => $this->tributo_cliente_id,
            'municipality_id' => $this->municipio_id,
        ];
    }
}