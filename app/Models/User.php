<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'nombre',
        'name',
        'email',
        'password',
        'rol',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'usuario_id');
    }

    public function turnosCaja(): HasMany
    {
        return $this->hasMany(TurnoCaja::class, 'usuario_id');
    }

    public function movimientosCaja(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class, 'usuario_id');
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(Kardex::class, 'usuario_id');
    }

    public function logsFactus(): HasMany
    {
        return $this->hasMany(LogIntegracionFactus::class, 'usuario_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(AuditoriaAccion::class, 'usuario_id');
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeAdmins($query)
    {
        return $query->where('rol', 'admin');
    }

    public function scopeCajeros($query)
    {
        return $query->where('rol', 'cajero');
    }

    public function scopeSupervisores($query)
    {
        return $query->where('rol', 'supervisor');
    }

    public function scopeBuscar($query, string $busqueda)
    {
        return $query->where('nombre', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('email', 'ILIKE', "%{$busqueda}%");
    }

    // Accessors
    public function getEsAdminAttribute(): bool
    {
        return $this->rol === 'admin';
    }

    public function getEsCajeroAttribute(): bool
    {
        return $this->rol === 'cajero';
    }

    public function getEsSupervisorAttribute(): bool
    {
        return $this->rol === 'supervisor';
    }

    public function getTurnoActivoAttribute(): ?TurnoCaja
    {
        return $this->turnosCaja()
                    ->where('estado', 'abierto')
                    ->latest()
                    ->first();
    }

    public function getTieneTurnoAbiertoAttribute(): bool
    {
        return $this->turno_activo !== null;
    }

    // Métodos
    public function puedeAcceder(string $permiso): bool
    {
        // Lógica de permisos según rol
        $permisos = [
            'admin' => ['*'], // Todos los permisos
            'supervisor' => [
                'ventas.*',
                'productos.*',
                'clientes.*',
                'caja.ver',
                'reportes.*',
            ],
            'cajero' => [
                'ventas.crear',
                'ventas.ver',
                'productos.ver',
                'clientes.ver',
                'clientes.crear',
                'caja.*',
            ],
        ];

        $permisosRol = $permisos[$this->rol] ?? [];

        // Admin tiene todos los permisos
        if (in_array('*', $permisosRol)) {
            return true;
        }

        // Verificar permiso específico o con wildcard
        foreach ($permisosRol as $permisoRol) {
            if ($permisoRol === $permiso) {
                return true;
            }

            // Verificar wildcard (ej: ventas.* coincide con ventas.crear)
            if (str_ends_with($permisoRol, '.*')) {
                $base = str_replace('.*', '', $permisoRol);
                if (str_starts_with($permiso, $base)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function registrarAuditoria(
        string $accion,
        string $tabla,
        ?int $registroId = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null
    ): AuditoriaAccion {
        return AuditoriaAccion::registrar(
            $this->id,
            $accion,
            $tabla,
            $registroId,
            $datosAnteriores,
            $datosNuevos
        );
    }
}
// ```

// ---

// ## ✅ TODOS LOS MODELOS COMPLETADOS

// ---

// ## 🎉 RESUMEN TOTAL DE MODELOS

// ### **Grupo 1: Catálogos (12 modelos)** ✅
// 1. Municipio
// 2. UnidadMedida
// 3. Tributo
// 4. TipoDocumentoIdentidad
// 5. TipoOrganizacion
// 6. MetodoPago
// 7. FormaPago
// 8. CodigoEstandar
// 9. TipoDocumentoFactura
// 10. TipoOperacion
// 11. RangoNumeracion
// 12. TributoCliente

// ### **Grupo 2: Empresa y Config (4 modelos)** ✅
// 13. Empresa
// 14. Establecimiento
// 15. ConfiguracionFactus
// 16. TokenFactus

// ### **Grupo 3: Negocio (6 modelos)** ✅
// 17. Cliente
// 18. Categoria
// 19. Producto
// 20. Kardex
// 21. Caja
// 22. TurnoCaja

// ### **Grupo 4: Ventas (9 modelos)** ✅
// 23. Venta
// 24. DetalleVenta
// 25. MovimientoCaja
// 26. RetencionDetalleVenta
// 27. RetencionVenta
// 28. DescuentoRecargoVenta
// 29. DocumentoRelacionado
// 30. NotaCredito
// 31. Mandato

// ### **Grupo 5: Sistema (3 modelos)** ✅
// 32. User
// 33. LogIntegracionFactus
// 34. AuditoriaAccion

// ---

// ## 📊 CARACTERÍSTICAS DE TODOS LOS MODELOS

// ✅ **34 modelos Eloquent completos**
// ✅ **Fillable/Hidden definidos**
// ✅ **Casts para todos los tipos de datos**
// ✅ **80+ relaciones (BelongsTo, HasMany, HasOne)**
// ✅ **150+ Scopes para consultas comunes**
// ✅ **100+ Accessors para cálculos y estados**
// ✅ **Métodos de negocio específicos**
// ✅ **Validaciones de estado**
// ✅ **SoftDeletes donde corresponde**
// ✅ **Sistema de permisos básico (User)**
// ✅ **Auditoría integrada**

// ---

// ## 🎯 ESTADO DEL PROYECTO
// ```
// ✅ SEMANA 1 - COMPLETADA
//    - Setup Laravel 12
//    - 35 tablas en BD
//    - Catálogos poblados
//    - Factus sincronizado

// ✅ SEMANA 2 - COMPLETADA
//    - 34 modelos Eloquent
//    - Relaciones completas
//    - Scopes y Accessors
//    - Métodos de negocio