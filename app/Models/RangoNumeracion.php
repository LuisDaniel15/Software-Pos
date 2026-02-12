<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RangoNumeracion extends Model
{
    use SoftDeletes;

    protected $table = 'rangos_numeracion';

    protected $fillable = [
        'document',
        'prefijo',
        'desde',
        'hasta',
        'consecutivo_actual',
        'numero_resolucion',
        'fecha_inicio',
        'fecha_fin',
        'technical_key',
        'is_expired',
        'is_active',
    ];

    protected $casts = [
        'desde' => 'integer',
        'hasta' => 'integer',
        'consecutivo_actual' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'is_expired' => 'boolean',
        'is_active' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('is_active', 1)
                    ->where('is_expired', false);
    }

    public function scopePorDocumento($query, string $documento)
    {
        return $query->where('document', $documento);
    }

    public function scopeFacturas($query)
    {
        return $query->where('document', 'Factura de Venta');
    }

    public function scopeNotasCredito($query)
    {
        return $query->where('document', 'Nota Crédito');
    }

    public function scopeDisponibles($query)
    {
        return $query->activos()
                    ->whereRaw('consecutivo_actual < hasta')
                    ->whereNotNull('desde')
                    ->whereNotNull('hasta')
                    ->whereNotNull('fecha_inicio')
                    ->whereNotNull('fecha_fin');
    }

    // Accessors
    public function getNumerosDisponiblesAttribute(): int
    {
        if (!$this->desde || !$this->hasta) {
            return 0;
        }
        return $this->hasta - $this->consecutivo_actual;
    }

    public function getPorcentajeUsoAttribute(): float
    {
        if (!$this->desde || !$this->hasta) {
            return 0;
        }
        $total = $this->hasta - $this->desde;
        $usados = $this->consecutivo_actual - $this->desde;
        return ($usados / $total) * 100;
    }

    public function getProximoNumeroAttribute(): ?string
    {
        if (!$this->desde || !$this->hasta) {
            return null;
        }
        
        if ($this->consecutivo_actual >= $this->hasta) {
            return null; // Rango agotado
        }
        
        $siguiente = $this->consecutivo_actual + 1;
        return $this->prefijo . $siguiente;
    }

    // Métodos
    public function incrementarConsecutivo(): bool
    {
        if ($this->consecutivo_actual >= $this->hasta) {
            return false; // Ya no hay números disponibles
        }

        $this->consecutivo_actual++;
        return $this->save();
    }

    public function estaAgotado(): bool
    {
        if (!$this->desde || !$this->hasta) {
            return true;
        }
        return $this->consecutivo_actual >= $this->hasta;
    }

    public function estaVigente(): bool
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return false;
        }
        
        $hoy = now();
        return $hoy->between($this->fecha_inicio, $this->fecha_fin);
    }
}