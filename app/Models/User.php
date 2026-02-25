<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'nombre',
        'name',
        'email',
        'password',
        'rol_id', // ← NUEVO
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

    // =====================================
    // RELACIONES
    // =====================================

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

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

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'usuario_id');
    }

    public function logsFactus(): HasMany
    {
        return $this->hasMany(LogIntegracionFactus::class, 'usuario_id');
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(AuditoriaAccion::class, 'usuario_id');
    }

    // =====================================
    // MÉTODOS DE SUCURSALES
    // =====================================

    /**
     * Obtener sucursales a las que el usuario tiene acceso
     */
    public function sucursalesAccesibles()
    {
        if (!$this->rol) {
            return collect([]);
        }

        // Admin tiene todas las sucursales
        if ($this->rol->es_admin) {
            return Sucursal::activas()->get();
        }

        return $this->rol->sucursales()->where('sucursales.activa', true)->get();
    }

    /**
     * Obtener IDs de sucursales accesibles
     */
    public function getSucursalesIdsAttribute(): array
    {
        if (!$this->rol) {
            return [];
        }

        return $this->rol->sucursales_ids;
    }

    /**
     * Verificar si tiene acceso a una sucursal
     */
    public function tieneAccesoASucursal(int $sucursalId): bool
    {
        if (!$this->rol) {
            return false;
        }

        return $this->rol->tieneAccesoASucursal($sucursalId);
    }

    /**
     * Obtener la primera sucursal accesible (para operaciones por defecto)
     */
    public function getSucursalPrincipalAttribute(): ?Sucursal
    {
        $sucursales = $this->sucursalesAccesibles();
        
        // Buscar sucursal principal primero
        $principal = $sucursales->firstWhere('es_principal', true);
        
        return $principal ?? $sucursales->first();
    }

    /**
     * Obtener ID de sucursal principal (para compatibilidad con código anterior)
     */
    public function getSucursalIdAttribute(): ?int
    {
        return $this->sucursal_principal?->id;
    }

    /**
     * Obtener nombre de sucursal principal
     */
    public function getSucursalNombreAttribute(): ?string
    {
        return $this->sucursal_principal?->nombre;
    }

    // =====================================
    // SCOPES
    // =====================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeAdmins($query)
    {
        return $query->whereHas('rol', function($q) {
            $q->where('nombre', 'admin');
        });
    }

    public function scopeCajeros($query)
    {
        return $query->whereHas('rol', function($q) {
            $q->where('nombre', 'cajero');
        });
    }

    public function scopeSupervisores($query)
    {
        return $query->whereHas('rol', function($q) {
            $q->where('nombre', 'supervisor');
        });
    }

    public function scopePorRol($query, int $rolId)
    {
        return $query->where('rol_id', $rolId);
    }

    public function scopeConAccesoASucursal($query, int $sucursalId)
    {
        return $query->whereHas('rol.sucursales', function($q) use ($sucursalId) {
            $q->where('sucursales.id', $sucursalId);
        })->orWhereHas('rol', function($q) {
            $q->where('nombre', 'admin');
        });
    }

    public function scopeBuscar($query, string $busqueda)
    {
        return $query->where('nombre', 'ILIKE', "%{$busqueda}%")
                    ->orWhere('email', 'ILIKE', "%{$busqueda}%");
    }

    // =====================================
    // ACCESSORS DE ROL
    // =====================================

    public function getEsAdminAttribute(): bool
    {
        return $this->rol && $this->rol->es_admin;
    }

    public function getEsCajeroAttribute(): bool
    {
        return $this->rol && $this->rol->es_cajero;
    }

    public function getEsSupervisorAttribute(): bool
    {
        return $this->rol && $this->rol->es_supervisor;
    }

    // Métodos helper (alias)
    public function esAdmin(): bool
    {
        return $this->es_admin;
    }

    public function esSupervisor(): bool
    {
        return $this->es_supervisor;
    }

    public function esCajero(): bool
    {
        return $this->es_cajero;
    }

    // =====================================
    // ACCESSORS DE TURNO
    // =====================================

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

    // =====================================
    // MÉTODOS DE PERMISOS
    // =====================================

    /**
     * Verificar si tiene un permiso específico
     */
    public function puedeAcceder(string $permiso): bool
    {
        // Admin tiene todos los permisos
        if ($this->es_admin) {
            return true;
        }

        // Obtener permisos del rol
        $permisosRol = $this->getPermisosDelRol();

        // Verificar si tiene el permiso exacto
        if (in_array($permiso, $permisosRol)) {
            return true;
        }

        // Verificar permisos con wildcard (ej: ventas.* incluye ventas.crear)
        foreach ($permisosRol as $permisoRol) {
            if (str_ends_with($permisoRol, '.*')) {
                $base = str_replace('.*', '', $permisoRol);
                if (str_starts_with($permiso, $base)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Alias para compatibilidad
     */
    public function hasPermission(string $permission): bool
    {
        return $this->puedeAcceder($permission);
    }

    /**
     * Obtener todos los permisos del rol del usuario
     */
    public function getPermisosDelRol(): array
    {
        if (!$this->rol) {
            return [];
        }

        $rolNombre = $this->rol->nombre;

        $permisos = [
            'admin' => [
                // Acceso total
                '*',
            ],
            'supervisor' => [
                // Ventas
                'ventas.ver',
                'ventas.crear',
                'ventas.detalle',
                'ventas.reintentar',
                
                // Productos
                'productos.*',
                
                // Clientes
                'clientes.*',
                
                // Inventario
                'inventario.ver',
                'inventario.crear',
                'inventario.trasladar',
                'inventario.ajustar',
                
                // Caja
                'caja.ver',
                'caja.turnos.ver',
                'caja.movimientos.ver',
                
                // Reportes
                'reportes.*',
                
                // Catálogos
                'catalogos.ver',
            ],
            'cajero' => [
                // Ventas (solo crear y ver propias)
                'ventas.crear',
                'ventas.ver',
                'ventas.detalle',
                
                // Productos (solo consultar)
                'productos.ver',
                'productos.buscar',
                
                // Clientes
                'clientes.ver',
                'clientes.crear',
                'clientes.buscar',
                
                // Inventario (solo ver)
                'inventario.ver',
                
                // Caja (todas las operaciones)
                'caja.abrir',
                'caja.cerrar',
                'caja.movimientos.crear',
                'caja.turnos.ver',
                
                // Catálogos (solo consultar)
                'catalogos.ver',
                
                // Reportes básicos
                'reportes.dashboard',
                'reportes.turno',
            ],
        ];

        return $permisos[$rolNombre] ?? [];
    }

    /**
     * Obtener permisos en formato legible
     */
    public function getPermisosFormateados(): array
    {
        $permisos = $this->getPermisosDelRol();
        
        if (in_array('*', $permisos)) {
            return ['Acceso total al sistema'];
        }

        $formateados = [];
        
        foreach ($permisos as $permiso) {
            $formateados[] = $this->formatearPermiso($permiso);
        }

        return $formateados;
    }

    /**
     * Formatear permiso para mostrar
     */
    protected function formatearPermiso(string $permiso): string
    {
        $traducciones = [
            'ventas.ver' => 'Ver ventas',
            'ventas.crear' => 'Crear ventas',
            'ventas.detalle' => 'Ver detalle de ventas',
            'ventas.reintentar' => 'Reintentar facturación',
            'ventas.*' => 'Gestión completa de ventas',
            
            'productos.ver' => 'Ver productos',
            'productos.crear' => 'Crear productos',
            'productos.editar' => 'Editar productos',
            'productos.buscar' => 'Buscar productos',
            'productos.*' => 'Gestión completa de productos',
            
            'clientes.ver' => 'Ver clientes',
            'clientes.crear' => 'Crear clientes',
            'clientes.editar' => 'Editar clientes',
            'clientes.buscar' => 'Buscar clientes',
            'clientes.*' => 'Gestión completa de clientes',
            
            'inventario.ver' => 'Ver inventario',
            'inventario.crear' => 'Registrar movimientos',
            'inventario.trasladar' => 'Trasladar entre sucursales',
            'inventario.ajustar' => 'Ajustar inventario',
            'inventario.*' => 'Gestión completa de inventario',
            
            'caja.abrir' => 'Abrir caja',
            'caja.cerrar' => 'Cerrar caja',
            'caja.ver' => 'Ver cajas',
            'caja.movimientos.crear' => 'Registrar movimientos de caja',
            'caja.movimientos.ver' => 'Ver movimientos de caja',
            'caja.turnos.ver' => 'Ver turnos de caja',
            'caja.*' => 'Gestión completa de caja',
            
            'usuarios.ver' => 'Ver usuarios',
            'usuarios.crear' => 'Crear usuarios',
            'usuarios.editar' => 'Editar usuarios',
            'usuarios.eliminar' => 'Eliminar usuarios',
            'usuarios.*' => 'Gestión completa de usuarios',
            
            'reportes.dashboard' => 'Ver dashboard',
            'reportes.ventas' => 'Reportes de ventas',
            'reportes.turno' => 'Reportes de turno',
            'reportes.*' => 'Todos los reportes',
            
            'catalogos.ver' => 'Ver catálogos',
            
            'configuracion.*' => 'Configuración del sistema',
        ];

        return $traducciones[$permiso] ?? $permiso;
    }

    // =====================================
    // AUDITORÍA
    // =====================================

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