<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoOperacion extends Model
{
    protected $table = 'tipos_operacion';

    protected $fillable = [
        'codigo',
        'descripcion',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    // Scopes
    public function scopeEstandar($query)
    {
        return $query->where('codigo', '10');
    }

    public function scopeMandatos($query)
    {
        return $query->where('codigo', '11');
    }

    public function scopeTransporte($query)
    {
        return $query->where('codigo', '12');
    }

    // Accessors
    public function getEsEstandarAttribute(): bool
    {
        return $this->codigo === '10';
    }

    public function getEsMandatosAttribute(): bool
    {
        return $this->codigo === '11';
    }

    public function getEsTransporteAttribute(): bool
    {
        return $this->codigo === '12';
    }
}