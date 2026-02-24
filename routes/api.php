<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VentaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\InventarioController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\UsuarioController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========================================
// RUTAS PÚBLICAS (Sin autenticación)
// ========================================

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// ========================================
// RUTAS PROTEGIDAS (Requieren autenticación)
// ========================================

Route::middleware('auth:sanctum')->group(function () {
    
    // ========================================
    // AUTH
    // ========================================
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // ========================================
    // VENTAS
    // ========================================
    Route::prefix('ventas')->group(function () {
        Route::get('/', [VentaController::class, 'index'])
            ->middleware('permission:ventas.ver');
            
        Route::post('/', [VentaController::class, 'store'])
            ->middleware('permission:ventas.crear');
            
        Route::get('del-dia', [VentaController::class, 'ventasDelDia'])
            ->middleware('permission:ventas.ver');
            
        Route::get('{id}', [VentaController::class, 'show'])
            ->middleware('permission:ventas.detalle');
            
        Route::post('{id}/reintentar-facturacion', [VentaController::class, 'reintentarFacturacion'])
            ->middleware('permission:ventas.reintentar');
    });

    // ========================================
    // PRODUCTOS
    // ========================================
    Route::prefix('productos')->group(function () {
        Route::get('/', [ProductoController::class, 'index'])
            ->middleware('permission:productos.ver');
            
        Route::post('/', [ProductoController::class, 'store'])
            ->middleware('permission:productos.crear');
            
        Route::get('buscar', [ProductoController::class, 'buscar'])
            ->middleware('permission:productos.buscar');
            
        Route::get('bajo-stock', [ProductoController::class, 'bajoStock'])
            ->middleware('permission:productos.ver');
            
        Route::get('{id}', [ProductoController::class, 'show'])
            ->middleware('permission:productos.ver');
            
        Route::put('{id}', [ProductoController::class, 'update'])
            ->middleware('permission:productos.editar');
    });

    // ========================================
    // CLIENTES
    // ========================================
    Route::prefix('clientes')->group(function () {
        Route::get('/', [ClienteController::class, 'index'])
            ->middleware('permission:clientes.ver');
            
        Route::post('/', [ClienteController::class, 'store'])
            ->middleware('permission:clientes.crear');
            
        Route::get('buscar', [ClienteController::class, 'buscar'])
            ->middleware('permission:clientes.buscar');
            
        Route::get('por-documento', [ClienteController::class, 'porDocumento'])
            ->middleware('permission:clientes.ver');
            
        Route::get('{id}', [ClienteController::class, 'show'])
            ->middleware('permission:clientes.ver');
            
        Route::put('{id}', [ClienteController::class, 'update'])
            ->middleware('permission:clientes.editar');
    });

    // ========================================
    // CAJA
    // ========================================
   Route::prefix('caja')->group(function () {
    // Cajas
    Route::get('cajas', [CajaController::class, 'listarCajas'])
        ->middleware('permission:caja.ver');
    
    Route::get('cajas-disponibles', [CajaController::class, 'cajasDisponibles'])
        ->middleware('permission:caja.ver');
    
    // Turnos
    Route::get('turno-actual', [CajaController::class, 'turnoActivo'])
        ->middleware('permission:caja.turnos.ver');
    
    Route::post('abrir-turno', [CajaController::class, 'abrirTurno'])
        ->middleware('permission:caja.abrir');
    
    Route::post('cerrar-turno', [CajaController::class, 'cerrarTurnoActual'])
        ->middleware('permission:caja.cerrar');
    
    Route::post('turnos/{id}/cerrar', [CajaController::class, 'cerrarTurno'])
        ->middleware('permission:caja.cerrar');
    
    Route::get('turnos', [CajaController::class, 'listarTurnos'])
        ->middleware('permission:caja.turnos.ver');
    
    Route::get('turnos/{id}', [CajaController::class, 'obtenerTurno'])
        ->middleware('permission:caja.turnos.ver');
    
    Route::get('turnos/{id}/resumen', [CajaController::class, 'resumenTurno'])
        ->middleware('permission:caja.turnos.ver');
    
    Route::get('turnos-del-dia', [CajaController::class, 'turnosDelDia'])
        ->middleware('permission:caja.turnos.ver');
    
    Route::get('turnos/historial', [CajaController::class, 'historial'])
        ->middleware('permission:caja.ver');
    
    // Movimientos
    Route::get('turnos/{turnoId}/movimientos', [CajaController::class, 'listarMovimientos'])
        ->middleware('permission:caja.turnos.ver');
    
    Route::post('movimientos', [CajaController::class, 'crearMovimiento'])
        ->middleware('permission:caja.movimientos.crear');
    
    Route::post('movimientos/ingreso', [CajaController::class, 'registrarIngreso'])
        ->middleware('permission:caja.movimientos.crear');
    
    Route::post('movimientos/egreso', [CajaController::class, 'registrarEgreso'])
        ->middleware('permission:caja.movimientos.crear');
});
    // ========================================
    // INVENTARIO
    // ========================================
    Route::prefix('inventario')->group(function () {
        // Movimientos
        Route::post('entrada', [InventarioController::class, 'registrarEntrada'])
            ->middleware('permission:inventario.entrada');
            
        Route::post('salida', [InventarioController::class, 'registrarSalida'])
            ->middleware('permission:inventario.salida');
            
        Route::post('ajuste', [InventarioController::class, 'registrarAjuste'])
            ->middleware('permission:inventario.ajuste');
        
        // Consultas
        Route::get('kardex/{productoId}', [InventarioController::class, 'kardexProducto'])
            ->middleware('permission:inventario.ver');
            
        Route::get('bajo-stock', [InventarioController::class, 'productosBajoStock'])
            ->middleware('permission:inventario.ver');
            
        Route::get('valor-inventario', [InventarioController::class, 'valorInventario'])
            ->middleware('permission:inventario.ver');
            
        Route::get('movimientos-del-dia', [InventarioController::class, 'movimientosDelDia'])
            ->middleware('permission:inventario.ver');
            
        Route::get('productos-mas-vendidos', [InventarioController::class, 'productosMasVendidos'])
            ->middleware('permission:inventario.ver');
    });

    // ========================================
    // USUARIOS (Solo Admin y Supervisor)
    // ========================================
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index'])
            ->middleware('permission:usuarios.ver');
            
        Route::post('/', [UsuarioController::class, 'store'])
            ->middleware('permission:usuarios.crear');
            
        Route::get('cajeros', [UsuarioController::class, 'cajeros'])
            ->middleware('permission:usuarios.ver');
            
        Route::get('rol/{rol}', [UsuarioController::class, 'porRol'])
            ->middleware('permission:usuarios.ver');
            
        Route::post('verificar-permiso', [UsuarioController::class, 'verificarPermiso']);
            
        Route::get('{id}', [UsuarioController::class, 'show'])
            ->middleware('permission:usuarios.ver');
            
        Route::put('{id}', [UsuarioController::class, 'update'])
            ->middleware('permission:usuarios.editar');
            
        Route::delete('{id}', [UsuarioController::class, 'destroy'])
            ->middleware('permission:usuarios.eliminar');
            
        Route::post('{id}/activar', [UsuarioController::class, 'activar'])
            ->middleware('permission:usuarios.editar');
            
        Route::post('{id}/cambiar-password', [UsuarioController::class, 'cambiarPassword']);
            
        Route::post('{id}/resetear-password', [UsuarioController::class, 'resetearPassword'])
            ->middleware('permission:usuarios.editar');
            
        Route::get('{id}/estadisticas', [UsuarioController::class, 'estadisticas'])
            ->middleware('permission:usuarios.ver');
    });

    // ========================================
    // CATÁLOGOS (Todos pueden ver)
    // ========================================
    Route::prefix('catalogos')->group(function () {
        Route::get('todos', [CatalogoController::class, 'todos'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('municipios', [CatalogoController::class, 'municipios'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('departamentos', [CatalogoController::class, 'departamentos'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('unidades-medida', [CatalogoController::class, 'unidadesMedida'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('tributos', [CatalogoController::class, 'tributos'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('tributos-cliente', [CatalogoController::class, 'tributosCliente'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('tipos-documento-identidad', [CatalogoController::class, 'tiposDocumentoIdentidad'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('tipos-organizacion', [CatalogoController::class, 'tiposOrganizacion'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('metodos-pago', [CatalogoController::class, 'metodosPago'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('formas-pago', [CatalogoController::class, 'formasPago'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('codigos-estandar', [CatalogoController::class, 'codigosEstandar'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('tipos-documento-factura', [CatalogoController::class, 'tiposDocumentoFactura'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('tipos-operacion', [CatalogoController::class, 'tiposOperacion'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('rangos-numeracion', [CatalogoController::class, 'rangosNumeracion'])
            ->middleware('permission:catalogos.ver');
            
        Route::get('categorias', [CatalogoController::class, 'categorias'])
            ->middleware('permission:catalogos.ver');
    });

    // ========================================
    // REPORTES
    // ========================================
    Route::prefix('reportes')->group(function () {
        Route::get('dashboard', [ReporteController::class, 'dashboard'])
            ->middleware('permission:reportes.dashboard');
            
        Route::get('ventas-por-periodo', [ReporteController::class, 'ventasPorPeriodo'])
            ->middleware('permission:reportes.ventas');
            
        Route::get('ventas-por-usuario', [ReporteController::class, 'ventasPorUsuario'])
            ->middleware('permission:reportes.ventas');
            
        Route::get('ventas-por-cliente', [ReporteController::class, 'ventasPorCliente'])
            ->middleware('permission:reportes.ventas');
            
        Route::get('arqueos-caja', [ReporteController::class, 'arqueosCaja'])
            ->middleware('permission:reportes.caja');
    });

    // Sucursales
Route::prefix('sucursales')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [SucursalController::class, 'index'])
        ->middleware('permission:sucursales.ver');
    
    Route::get('/actual', [SucursalController::class, 'sucursalActual']);
    
    Route::get('/{id}', [SucursalController::class, 'show'])
        ->middleware('permission:sucursales.ver');
});
});