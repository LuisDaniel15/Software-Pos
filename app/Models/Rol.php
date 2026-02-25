<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =====================================
    // RELACIONES
    // =====================================

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class, 'rol_sucursal')
            ->withTimestamps();
    }

    // =====================================
    // SCOPES
    // =====================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // =====================================
    // ACCESSORS
    // =====================================

    public function getEsAdminAttribute(): bool
    {
        return strtolower($this->nombre) === 'admin';
    }

    public function getEsSupervisorAttribute(): bool
    {
        return strtolower($this->nombre) === 'supervisor';
    }

    public function getEsCajeroAttribute(): bool
    {
        return strtolower($this->nombre) === 'cajero';
    }

    // =====================================
    // MÉTODOS
    // =====================================

    /**
     * Verificar si el rol tiene acceso a una sucursal
     */
    public function tieneAccesoASucursal(int $sucursalId): bool
    {
        // Admin tiene acceso a todas
        if ($this->es_admin) {
            return true;
        }

        return $this->sucursales()->where('sucursales.id', $sucursalId)->exists();
    }

    /**
     * Obtener IDs de sucursales asignadas
     */
    public function getSucursalesIdsAttribute(): array
    {
        // Admin tiene todas las sucursales
        if ($this->es_admin) {
            return Sucursal::pluck('id')->toArray();
        }

        return $this->sucursales()->pluck('sucursales.id')->toArray();
    }

    /**
     * Asignar sucursales al rol
     */
    public function asignarSucursales(array $sucursalesIds): void
    {
        $this->sucursales()->sync($sucursalesIds);
    }
}