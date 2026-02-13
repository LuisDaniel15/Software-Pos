<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'codigo_referencia',
        'nombre',
        'descripcion',
        'precio_venta',
        'porcentaje_iva',
        'costo_compra',
        'stock_actual',
        'stock_minimo',
        'unidad_medida_id',
        'codigo_estandar_id',
        'codigo_estandar_valor',
        'tributo_id',
        'es_excluido',
        'permite_mandato',
        'activo',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'costo_compra' => 'decimal:2',
        'stock_actual' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'es_excluido' => 'integer',
        'permite_mandato' => 'boolean',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function unidadMedida(): BelongsTo
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function codigoEstandar(): BelongsTo
    {
        return $this->belongsTo(CodigoEstandar::class);
    }

    public function tributo(): BelongsTo
    {
        return $this->belongsTo(Tributo::class);
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(Kardex::class);
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar($query, string $busqueda)
    {
        return $query->where(function($q) use ($busqueda) {
            $q->where('nombre', 'ILIKE', "%{$busqueda}%")
              ->orWhere('codigo_referencia', 'ILIKE', "%{$busqueda}%")
              ->orWhere('codigo_estandar_valor', 'ILIKE', "%{$busqueda}%");
        });
    }

    public function scopeConStock($query)
    {
        return $query->where('stock_actual', '>', 0);
    }

    public function scopeBajoStock($query)
    {
        return $query->whereRaw('stock_actual <= stock_minimo');
    }

    public function scopePorCategoria($query, int $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }

    // Accessors
    public function getPrecioSinIvaAttribute(): float
    {
        $porcentaje = (float) str_replace(',', '.', $this->porcentaje_iva);
        return $this->precio_venta / (1 + ($porcentaje / 100));
    }

    public function getValorIvaAttribute(): float
    {
        return $this->precio_venta - $this->precio_sin_iva;
    }

    public function getEsExcluidoBooleanoAttribute(): bool
    {
        return $this->es_excluido === 1;
    }

    public function getTieneStockAttribute(): bool
    {
        return $this->stock_actual > 0;
    }

    public function getEstaBajoStockAttribute(): bool
    {
        return $this->stock_minimo && $this->stock_actual <= $this->stock_minimo;
    }

    public function getMargenAttribute(): ?float
    {
        if (!$this->costo_compra || $this->costo_compra == 0) {
            return null;
        }
        
        return (($this->precio_venta - $this->costo_compra) / $this->costo_compra) * 100;
    }

    public function getUtilidadAttribute(): ?float
    {
        if (!$this->costo_compra) {
            return null;
        }
        
        return $this->precio_venta - $this->costo_compra;
    }

    // Métodos
    public function actualizarStock(float $cantidad, string $tipo = 'entrada'): bool
    {
        if ($tipo === 'entrada') {
            $this->stock_actual += $cantidad;
        } else {
            $this->stock_actual -= $cantidad;
        }

        return $this->save();
    }

    public function tieneStockDisponible(float $cantidad): bool
    {
        return $this->stock_actual >= $cantidad;
    }

    public function getDatosParaFactus(int $cantidad, float $descuento = 0): array
    {
        return [
            'code_reference' => $this->codigo_referencia,
            'name' => $this->nombre,
            'quantity' => $cantidad,
            'price' => (float) $this->precio_venta,
            'tax_rate' => $this->porcentaje_iva,
            'discount_rate' => $descuento,
            'unit_measure_id' => $this->unidad_medida_id,
            'standard_code_id' => $this->codigo_estandar_id,
            'is_excluded' => $this->es_excluido,
            'tribute_id' => $this->tributo_id,
        ];
    }
}