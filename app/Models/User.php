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
        'rol',
        'activo',
        'sucursal_id', // ← AGREGAR
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

    // Métodos de Permisos
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
     * Obtener todos los permisos del rol del usuario
     */
    public function getPermisosDelRol(): array
    {
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
                'inventario.entrada',
                'inventario.salida',
                'inventario.ajuste',
                
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

        return $permisos[$this->rol] ?? [];
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
            'inventario.entrada' => 'Registrar entradas',
            'inventario.salida' => 'Registrar salidas',
            'inventario.ajuste' => 'Ajustar inventario',
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

 public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    // =====================================
    // SCOPES (agregar)
    // =====================================

    public function scopePorSucursal($query, int $sucursalId)
    {
        return $query->where('sucursal_id', $sucursalId);
    }

    // =====================================
    // ACCESSORS (agregar)
    // =====================================

    public function getSucursalNombreAttribute(): ?string
    {
        return $this->sucursal?->nombre;
    }
}