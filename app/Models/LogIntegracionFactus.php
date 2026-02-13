<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogIntegracionFactus extends Model
{
    protected $table = 'logs_integracion_factus';

    public $timestamps = false;

    protected $fillable = [
        'tipo_operacion',
        'request',
        'response',
        'codigo_http',
        'exitoso',
        'venta_id',
        'usuario_id',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'request' => 'array',
        'response' => 'array',
        'codigo_http' => 'integer',
        'exitoso' => 'boolean',
        'created_at' => 'datetime',
    ];

    // Relaciones
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeExitosos($query)
    {
        return $query->where('exitoso', true);
    }

    public function scopeFallidos($query)
    {
        return $query->where('exitoso', false);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_operacion', $tipo);
    }

    public function scopeAutenticaciones($query)
    {
        return $query->where('tipo_operacion', 'auth');
    }

    public function scopeCreacionFacturas($query)
    {
        return $query->where('tipo_operacion', 'crear_factura');
    }

    public function scopeConsultas($query)
    {
        return $query->where('tipo_operacion', 'consultar_estado');
    }

    public function scopePorVenta($query, int $ventaId)
    {
        return $query->where('venta_id', $ventaId);
    }

    public function scopeRecientes($query, int $limit = 100)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('created_at', [$desde, $hasta]);
    }

    // Accessors
    public function getEsExitosoAttribute(): bool
    {
        return $this->exitoso === true;
    }

    public function getEsErrorAttribute(): bool
    {
        return !$this->es_exitoso;
    }

    public function getMensajeErrorAttribute(): ?string
    {
        if ($this->es_exitoso || !$this->response) {
            return null;
        }

        return $this->response['message'] 
            ?? $this->response['error'] 
            ?? $this->response['error_description'] 
            ?? 'Error desconocido';
    }

    // Métodos
    public static function registrar(
        string $tipoOperacion,
        array $request,
        ?array $response,
        ?int $codigoHttp,
        bool $exitoso,
        ?int $ventaId = null,
        ?int $usuarioId = null,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'tipo_operacion' => $tipoOperacion,
            'request' => $request,
            'response' => $response,
            'codigo_http' => $codigoHttp,
            'exitoso' => $exitoso,
            'venta_id' => $ventaId,
            'usuario_id' => $usuarioId,
            'ip_address' => $ipAddress ?? request()->ip(),
            'created_at' => now(),
        ]);
    }

    public static function limpiarAntiguos(int $diasAMantener = 90): int
    {
        $fecha = now()->subDays($diasAMantener);
        return self::where('created_at', '<', $fecha)->delete();
    }
}