<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaAccion extends Model
{
    protected $table = 'auditoria_acciones';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'accion',
        'tabla_afectada',
        'registro_id',
        'datos_anteriores',
        'datos_nuevos',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'created_at' => 'datetime',
    ];

    // Relaciones
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePorUsuario($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopePorAccion($query, string $accion)
    {
        return $query->where('accion', $accion);
    }

    public function scopePorTabla($query, string $tabla)
    {
        return $query->where('tabla_afectada', $tabla);
    }

    public function scopePorRegistro($query, string $tabla, int $registroId)
    {
        return $query->where('tabla_afectada', $tabla)
                    ->where('registro_id', $registroId);
    }

    public function scopeRecientes($query, int $limit = 100)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function scopePorFecha($query, $desde, $hasta)
    {
        return $query->whereBetween('created_at', [$desde, $hasta]);
    }

    public function scopeCreaciones($query)
    {
        return $query->where('accion', 'LIKE', 'crear_%');
    }

    public function scopeActualizaciones($query)
    {
        return $query->where('accion', 'LIKE', 'actualizar_%');
    }

    public function scopeEliminaciones($query)
    {
        return $query->where('accion', 'LIKE', 'eliminar_%');
    }

    // Accessors
    public function getEsCreacionAttribute(): bool
    {
        return str_starts_with($this->accion, 'crear_');
    }

    public function getEsActualizacionAttribute(): bool
    {
        return str_starts_with($this->accion, 'actualizar_');
    }

    public function getEsEliminacionAttribute(): bool
    {
        return str_starts_with($this->accion, 'eliminar_');
    }

    public function getCambiosAttribute(): array
    {
        if (!$this->datos_anteriores || !$this->datos_nuevos) {
            return [];
        }

        $cambios = [];
        foreach ($this->datos_nuevos as $campo => $valorNuevo) {
            $valorAnterior = $this->datos_anteriores[$campo] ?? null;
            
            if ($valorAnterior !== $valorNuevo) {
                $cambios[$campo] = [
                    'anterior' => $valorAnterior,
                    'nuevo' => $valorNuevo,
                ];
            }
        }

        return $cambios;
    }

    // Métodos
    public static function registrar(
        int $usuarioId,
        string $accion,
        string $tablaAfectada,
        ?int $registroId = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'tabla_afectada' => $tablaAfectada,
            'registro_id' => $registroId,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'ip_address' => $ipAddress ?? request()->ip(),
            'created_at' => now(),
        ]);
    }

    public static function limpiarAntiguos(int $diasAMantener = 365): int
    {
        $fecha = now()->subDays($diasAMantener);
        return self::where('created_at', '<', $fecha)->delete();
    }
}