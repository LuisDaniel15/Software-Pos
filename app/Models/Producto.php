<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'codigo_referencia',
        'nombre',
        'descripcion',
        'precio_venta',
        'porcentaje_iva',
        'costo_compra',
        'unidad_medida_id',
        'codigo_estandar_id',
        'tributo_id',
        'es_excluido',
        'permite_mandato',
        'activo',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'porcentaje_iva' => 'decimal:2',
        'costo_compra' => 'decimal:2',
        'es_excluido' => 'boolean',
        'permite_mandato' => 'boolean',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // =====================================
    // RELACIONES
    // =====================================

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

    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }

    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    // =====================================
    // SCOPES
    // =====================================

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where('activo', false);
    }

    public function scopePorCategoria(Builder $query, int $categoriaId): Builder
    {
        return $query->where('categoria_id', $categoriaId);
    }

    // ❌ REMOVER estos scopes (ahora se manejan por inventario)
    // public function scopeBajoStock(Builder $query): Builder
    // public function scopeSinStock(Builder $query): Builder
    // public function scopeConStock(Builder $query): Builder

    /**
     * Productos con stock en una sucursal específica
     */
    public function scopeConStockEnSucursal(Builder $query, int $sucursalId): Builder
    {
        return $query->whereHas('inventarios', function($q) use ($sucursalId) {
            $q->where('sucursal_id', $sucursalId)
              ->where('stock_actual', '>', 0);
        });
    }

    /**
     * Productos bajo stock en una sucursal específica
     */
    public function scopeBajoStockEnSucursal(Builder $query, int $sucursalId): Builder
    {
        return $query->whereHas('inventarios', function($q) use ($sucursalId) {
            $q->where('sucursal_id', $sucursalId)
              ->whereColumn('stock_actual', '<=', 'stock_minimo')
              ->where('stock_actual', '>', 0);
        });
    }

    // =====================================
    // MÉTODOS DE INVENTARIO POR SUCURSAL
    // =====================================

    /**
     * Obtener inventario de una sucursal específica
     */
    public function inventarioEnSucursal(int $sucursalId): ?Inventario
    {
        return $this->inventarios()
            ->where('sucursal_id', $sucursalId)
            ->first();
    }

    /**
     * Obtener stock en una sucursal
     */
    public function stockEnSucursal(int $sucursalId): float
    {
        $inventario = $this->inventarioEnSucursal($sucursalId);
        return $inventario ? $inventario->stock_actual : 0;
    }

    /**
     * Verificar si tiene stock en una sucursal
     */
    public function tieneStockEnSucursal(int $sucursalId, float $cantidad = 1): bool
    {
        return $this->stockEnSucursal($sucursalId) >= $cantidad;
    }

    /**
     * Obtener stock total en todas las sucursales
     */
    public function getStockTotalAttribute(): float
    {
        return $this->inventarios()->sum('stock_actual');
    }

    /**
     * Obtener valor total del inventario en todas las sucursales
     */
    public function getValorInventarioTotalAttribute(): float
    {
        return $this->inventarios()
            ->get()
            ->sum(fn($inv) => $inv->stock_actual * $inv->costo_promedio);
    }

    /**
     * Obtener sucursales donde hay stock disponible
     */
    public function sucursalesConStock()
    {
        return $this->inventarios()
            ->with('sucursal')
            ->where('stock_actual', '>', 0)
            ->get()
            ->pluck('sucursal');
    }

    // =====================================
    // MÉTODOS HEREDADOS (mantener compatibilidad)
    // =====================================

    // ❌ REMOVER estos métodos
    // public function actualizarStock(int $cantidad, string $tipo): bool
    // public function tieneStockDisponible(int $cantidad): bool

    // =====================================
    // ACCESSORS
    // =====================================

    public function getPrecioConIvaAttribute(): float
    {
        return $this->precio_venta * (1 + ($this->porcentaje_iva / 100));
    }

    public function getValorIvaAttribute(): float
    {
        return $this->precio_venta * ($this->porcentaje_iva / 100);
    }

    public function getEsActivoAttribute(): bool
    {
        return $this->activo;
    }

    // ❌ REMOVER estos accessors
    // public function getEsBajoStockAttribute(): bool
    // public function getEsSinStockAttribute(): bool
    // public function getEsConStockAttribute(): bool

    // =====================================
    // MÉTODOS
    // =====================================

    public function activar(): bool
    {
        return $this->update(['activo' => true]);
    }

    public function desactivar(): bool
    {
        return $this->update(['activo' => false]);
    }

    public function calcularPrecioFinal(float $cantidad = 1): float
    {
        return $this->precio_con_iva * $cantidad;
    }
}