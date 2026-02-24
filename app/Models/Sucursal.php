<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sucursal extends Model
{
    use SoftDeletes;

    protected $table = 'sucursales';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'direccion',
        'telefono',
        'email',
        'ciudad',
        'municipio_id',
        'es_principal',
        'activa',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
        'activa' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // =====================================
    // RELACIONES
    // =====================================

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class);
    }

    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    // =====================================
    // SCOPES
    // =====================================

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopePrincipales($query)
    {
        return $query->where('es_principal', true);
    }

    public function scopePorEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    // =====================================
    // ACCESSORS
    // =====================================

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->codigo} - {$this->nombre}";
    }

    public function getCantidadUsuariosAttribute(): int
    {
        return $this->usuarios()->count();
    }

    public function getCantidadCajasAttribute(): int
    {
        return $this->cajas()->count();
    }

    // =====================================
    // MÉTODOS
    // =====================================

    public function activar(): bool
    {
        return $this->update(['activa' => true]);
    }

    public function desactivar(): bool
    {
        return $this->update(['activa' => false]);
    }

    public function establecerComoPrincipal(): bool
    {
        // Quitar principal a otras sucursales de la misma empresa
        static::where('empresa_id', $this->empresa_id)
            ->where('id', '!=', $this->id)
            ->update(['es_principal' => false]);

        return $this->update(['es_principal' => true]);
    }
}