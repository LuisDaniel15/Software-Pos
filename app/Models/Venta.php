<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model
{
    use SoftDeletes;

    protected $table = 'ventas';

    protected $fillable = [
        'turno_caja_id',
        'cliente_id',
        'usuario_id',
        'establecimiento_id',
        'numero_venta',
        'reference_code',
        'tipo_documento_id',
        'rango_numeracion_id',
        'numero_factura_dian',
        'cufe',
        'qr_url',
        'qr_image',
        'estado_dian',
        'fecha_validacion_dian',
        'errores_dian',
        'respuesta_factus',
        'fecha_venta',
        'forma_pago_id',
        'fecha_vencimiento',
        'metodo_pago_id',
        'tipo_operacion_id',
        'orden_numero',
        'orden_fecha',
        'periodo_inicio',
        'periodo_hora_inicio',
        'periodo_fin',
        'periodo_hora_fin',
        'subtotal',
        'total_iva',
        'total_descuentos',
        'total_recargos',
        'total',
        'gross_value',
        'taxable_amount',
        'estado',
        'observaciones',
        'enviar_email',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'fecha_vencimiento' => 'date',
        'fecha_validacion_dian' => 'datetime',
        'orden_fecha' => 'date',
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'subtotal' => 'decimal:2',
        'total_iva' => 'decimal:2',
        'total_descuentos' => 'decimal:2',
        'total_recargos' => 'decimal:2',
        'total' => 'decimal:2',
        'gross_value' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'enviar_email' => 'boolean',
        'errores_dian' => 'array',
        'respuesta_factus' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function turnoCaja(): BelongsTo
    {
        return $this->belongsTo(TurnoCaja::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumentoFactura::class, 'tipo_documento_id');
    }

    public function rangoNumeracion(): BelongsTo
    {
        return $this->belongsTo(RangoNumeracion::class);
    }

    public function formaPago(): BelongsTo
    {
        return $this->belongsTo(FormaPago::class);
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class);
    }

    public function tipoOperacion(): BelongsTo
    {
        return $this->belongsTo(TipoOperacion::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function retenciones(): HasMany
    {
        return $this->hasMany(RetencionVenta::class);
    }

    public function descuentosRecargos(): HasMany
    {
        return $this->hasMany(DescuentoRecargoVenta::class);
    }

    public function documentosRelacionados(): HasMany
    {
        return $this->hasMany(DocumentoRelacionado::class);
    }

    public function notaCredito(): HasOne
    {
        return $this->hasOne(NotaCredito::class, 'venta_nota_id');
    }

    public function notaCreditoOriginal(): HasOne
    {
        return $this->hasOne(NotaCredito::class, 'venta_original_id');
    }

    public function movimientoCaja(): HasOne
    {
        return $this->hasOne(MovimientoCaja::class);
    }

    // Scopes
    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
    }

    public function scopeAnuladas($query)
    {
        return $query->where('estado', 'anulada');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeValidadas($query)
    {
        return $query->where('estado_dian', 'validada');
    }

    public function scopeRechazadas($query)
    {
        return $query->where('estado_dian', 'rechazada');
    }

    public function scopePendientesFacturacion($query)
    {
        return $query->where('estado_dian', 'pendiente');
    }

    public function scopePorCliente($query, int $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    public function scopePorUsuario($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_venta', [$desde, $hasta]);
    }

    public function scopeDelDia($query)
    {
        return $query->whereDate('fecha_venta', today());
    }

    // Accessors
    public function getEstaCompletadaAttribute(): bool
    {
        return $this->estado === 'completada';
    }

    public function getEstaAnuladaAttribute(): bool
    {
        return $this->estado === 'anulada';
    }

    public function getEstaPendienteAttribute(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function getEstaValidadaAttribute(): bool
    {
        return $this->estado_dian === 'validada';
    }

    public function getEstaRechazadaAttribute(): bool
    {
        return $this->estado_dian === 'rechazada';
    }

    public function getEsPendienteFacturacionAttribute(): bool
    {
        return $this->estado_dian === 'pendiente';
    }

    public function getEsFacturaAttribute(): bool
    {
        return $this->tipoDocumento && $this->tipoDocumento->es_factura;
    }

    public function getEsNotaCreditoAttribute(): bool
    {
        return $this->tipoDocumento && $this->tipoDocumento->es_nota_credito;
    }

    public function getEsContadoAttribute(): bool
    {
        return $this->formaPago && $this->formaPago->es_contado;
    }

    public function getEsCreditoAttribute(): bool
    {
        return $this->formaPago && $this->formaPago->es_credito;
    }

    public function getTieneErroresAttribute(): bool
    {
        return !empty($this->errores_dian);
    }

    public function getCantidadItemsAttribute(): int
    {
        return $this->detalles()->count();
    }

    public function getCantidadProductosAttribute(): int
    {
        return $this->detalles()->sum('cantidad');
    }

    // Métodos
    public function puedeAnularse(): bool
    {
        return $this->esta_completada && !$this->esta_validada;
    }

    public function puedeReintentarFacturacion(): bool
    {
        return $this->esta_rechazada || $this->es_pendiente_facturacion;
    }
}