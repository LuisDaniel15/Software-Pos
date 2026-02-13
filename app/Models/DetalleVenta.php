<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DetalleVenta extends Model
{
    protected $table = 'detalle_ventas';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'codigo_referencia',
        'nombre_producto',
        'cantidad',
        'precio_unitario',
        'porcentaje_iva',
        'porcentaje_descuento',
        'precio_base',
        'descuento',
        'subtotal',
        'total_iva',
        'total',
        'unidad_medida_id',
        'codigo_estandar_id',
        'tributo_id',
        'es_excluido',
        'nota_item',
        'scheme_id',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'precio_base' => 'decimal:2',
        'descuento' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total_iva' => 'decimal:2',
        'total' => 'decimal:2',
        'es_excluido' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
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

    public function retenciones(): HasMany
    {
        return $this->hasMany(RetencionDetalleVenta::class);
    }

    public function mandato(): HasOne
    {
        return $this->hasOne(Mandato::class);
    }

    // Accessors
    public function getEsExcluidoBooleanoAttribute(): bool
    {
        return $this->es_excluido === 1;
    }

    public function getTieneDescuentoAttribute(): bool
    {
        return $this->porcentaje_descuento > 0;
    }

    public function getTieneRetencionesAttribute(): bool
    {
        return $this->retenciones()->exists();
    }

    public function getTotalRetencionesAttribute(): float
    {
        return $this->retenciones()->sum('valor') ?? 0;
    }

    // Métodos
    public static function calcularTotales(
        int $cantidad,
        float $precioUnitario,
        string $porcentajeIva,
        float $porcentajeDescuento = 0
    ): array {
        // Precio total con IVA
        $totalConIva = $cantidad * $precioUnitario;
        
        // Calcular descuento
        $descuento = $totalConIva * ($porcentajeDescuento / 100);
        $totalConDescuento = $totalConIva - $descuento;
        
        // Calcular base sin IVA
        $porcentajeIvaDecimal = (float) str_replace(',', '.', $porcentajeIva);
        $precioBase = $totalConDescuento / (1 + ($porcentajeIvaDecimal / 100));
        
        // Calcular IVA
        $totalIva = $totalConDescuento - $precioBase;
        
        return [
            'precio_base' => round($precioBase, 2),
            'descuento' => round($descuento, 2),
            'subtotal' => round($precioBase, 2),
            'total_iva' => round($totalIva, 2),
            'total' => round($totalConDescuento, 2),
        ];
    }
}