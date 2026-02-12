<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormaPago extends Model
{
    protected $table = 'formas_pago';

    protected $fillable = [
        'codigo',
        'nombre',
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
    public function scopeContado($query)
    {
        return $query->where('codigo', '1');
    }

    public function scopeCredito($query)
    {
        return $query->where('codigo', '2');
    }

    // Accessors
    public function getEsContadoAttribute(): bool
    {
        return $this->codigo === '1';
    }

    public function getEsCreditoAttribute(): bool
    {
        return $this->codigo === '2';
    }
}