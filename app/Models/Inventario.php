<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Inventario extends Model
{
    protected $table = 'inventarios';

    protected $fillable = [
        'producto_id',
        'sucursal_id',
        'stock_actual',
        'stock_minimo',
        'stock_maximo',
        'costo_promedio',
        'ultima_entrada',
        'ultima_salida',
    ];

    protected $casts = [
        'stock_actual' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_maximo' => 'decimal:2',
        'costo_promedio' => 'decimal:2',
        'ultima_entrada' => 'datetime',
        'ultima_salida' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =====================================
    // RELACIONES
    // =====================================

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    // =====================================
    // SCOPES
    // =====================================

    public function scopePorSucursal(Builder $query, int $sucursalId): Builder
    {
        return $query->where('sucursal_id', $sucursalId);
    }

    public function scopePorProducto(Builder $query, int $productoId): Builder
    {
        return $query->where('producto_id', $productoId);
    }

    public function scopeConStock(Builder $query): Builder
    {
        return $query->where('stock_actual', '>', 0);
    }

    public function scopeSinStock(Builder $query): Builder
    {
        return $query->where('stock_actual', '<=', 0);
    }

    public function scopeBajoStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('stock_actual', '>', 0);
    }

    public function scopeStockCritico(Builder $query): Builder
    {
        return $query->whereRaw('stock_actual <= (stock_minimo * 0.5)')
            ->where('stock_actual', '>', 0);
    }

    // =====================================
    // ACCESSORS
    // =====================================

    public function getTieneStockAttribute(): bool
    {
        return $this->stock_actual > 0;
    }

    public function getEsBajoStockAttribute(): bool
    {
        return $this->stock_actual > 0 && $this->stock_actual <= $this->stock_minimo;
    }

    public function getEsStockCriticoAttribute(): bool
    {
        return $this->stock_actual > 0 && 
               $this->stock_actual <= ($this->stock_minimo * 0.5);
    }

    public function getSinStockAttribute(): bool
    {
        return $this->stock_actual <= 0;
    }

    public function getPorcentajeStockAttribute(): ?float
    {
        if (!$this->stock_maximo || $this->stock_maximo <= 0) {
            return null;
        }

        return round(($this->stock_actual / $this->stock_maximo) * 100, 2);
    }

    public function getValorInventarioAttribute(): float
    {
        return round($this->stock_actual * $this->costo_promedio, 2);
    }

    // =====================================
    // MÉTODOS
    // =====================================

    /**
     * Incrementar stock
     */
    public function incrementarStock(float $cantidad, ?float $costoUnitario = null): bool
    {
        $this->stock_actual += $cantidad;
        $this->ultima_entrada = now();

        // Actualizar costo promedio si se proporciona
        if ($costoUnitario !== null && $costoUnitario > 0) {
            $this->actualizarCostoPromedio($cantidad, $costoUnitario);
        }

        return $this->save();
    }

    /**
     * Decrementar stock
     */
    public function decrementarStock(float $cantidad): bool
    {
        if ($cantidad > $this->stock_actual) {
            throw new \Exception("Stock insuficiente. Disponible: {$this->stock_actual}");
        }

        $this->stock_actual -= $cantidad;
        $this->ultima_salida = now();

        return $this->save();
    }

    /**
     * Actualizar costo promedio ponderado
     */
    public function actualizarCostoPromedio(float $cantidadNueva, float $costoNuevo): void
    {
        $valorActual = $this->stock_actual * $this->costo_promedio;
        $valorNuevo = $cantidadNueva * $costoNuevo;
        $stockTotal = $this->stock_actual + $cantidadNueva;

        if ($stockTotal > 0) {
            $this->costo_promedio = ($valorActual + $valorNuevo) / $stockTotal;
        }
    }

    /**
     * Ajustar stock (puede ser positivo o negativo)
     */
    public function ajustarStock(float $nuevoStock, ?string $motivo = null): bool
    {
        $diferencia = $nuevoStock - $this->stock_actual;
        
        $this->stock_actual = $nuevoStock;
        
        if ($diferencia > 0) {
            $this->ultima_entrada = now();
        } elseif ($diferencia < 0) {
            $this->ultima_salida = now();
        }

        return $this->save();
    }

    /**
     * Verificar disponibilidad
     */
    public function tieneDisponibilidad(float $cantidad): bool
    {
        return $this->stock_actual >= $cantidad;
    }

    /**
     * Obtener cantidad disponible para venta
     */
    public function getCantidadDisponible(): float
    {
        return max(0, $this->stock_actual);
    }

    /**
     * Crear o actualizar inventario
     */
    public static function obtenerOCrear(int $productoId, int $sucursalId): self
    {
        return static::firstOrCreate(
            [
                'producto_id' => $productoId,
                'sucursal_id' => $sucursalId,
            ],
            [
                'stock_actual' => 0,
                'stock_minimo' => 0,
                'costo_promedio' => 0,
            ]
        );
    }
}