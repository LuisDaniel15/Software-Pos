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
        Route::get('/', [VentaController::class, 'index']);
        Route::post('/', [VentaController::class, 'store']);
        Route::get('del-dia', [VentaController::class, 'ventasDelDia']);
        Route::get('{id}', [VentaController::class, 'show']);
        Route::post('{id}/reintentar-facturacion', [VentaController::class, 'reintentarFacturacion']);
    });

    // ========================================
    // PRODUCTOS
    // ========================================
    Route::prefix('productos')->group(function () {
        Route::get('/', [ProductoController::class, 'index']);
        Route::post('/', [ProductoController::class, 'store']);
        Route::get('buscar', [ProductoController::class, 'buscar']);
        Route::get('bajo-stock', [ProductoController::class, 'bajoStock']);
        Route::get('{id}', [ProductoController::class, 'show']);
        Route::put('{id}', [ProductoController::class, 'update']);
    });

    // ========================================
    // CLIENTES
    // ========================================
    Route::prefix('clientes')->group(function () {
        Route::get('/', [ClienteController::class, 'index']);
        Route::post('/', [ClienteController::class, 'store']);
        Route::get('buscar', [ClienteController::class, 'buscar']);
        Route::get('por-documento', [ClienteController::class, 'porDocumento']);
        Route::get('{id}', [ClienteController::class, 'show']);
        Route::put('{id}', [ClienteController::class, 'update']);
    });

    // ========================================
    // CAJA
    // ========================================
    Route::prefix('caja')->group(function () {
        // Cajas
        Route::get('cajas-disponibles', [CajaController::class, 'cajasDisponibles']);
        
        // Turnos
        Route::get('turno-activo', [CajaController::class, 'turnoActivo']);
        Route::post('abrir-turno', [CajaController::class, 'abrirTurno']);
        Route::post('turnos/{id}/cerrar', [CajaController::class, 'cerrarTurno']);
        Route::get('turnos/{id}/resumen', [CajaController::class, 'resumenTurno']);
        Route::get('turnos-del-dia', [CajaController::class, 'turnosDelDia']);
        Route::get('turnos/historial', [CajaController::class, 'historial']);
        
        // Movimientos
        Route::post('movimientos/ingreso', [CajaController::class, 'registrarIngreso']);
        Route::post('movimientos/egreso', [CajaController::class, 'registrarEgreso']);
    });

    // ========================================
    // INVENTARIO
    // ========================================
    Route::prefix('inventario')->group(function () {
        // Movimientos
        Route::post('entrada', [InventarioController::class, 'registrarEntrada']);
        Route::post('salida', [InventarioController::class, 'registrarSalida']);
        Route::post('ajuste', [InventarioController::class, 'registrarAjuste']);
        
        // Consultas
        Route::get('kardex/{productoId}', [InventarioController::class, 'kardexProducto']);
        Route::get('bajo-stock', [InventarioController::class, 'productosBajoStock']);
        Route::get('valor-inventario', [InventarioController::class, 'valorInventario']);
        Route::get('movimientos-del-dia', [InventarioController::class, 'movimientosDelDia']);
        Route::get('productos-mas-vendidos', [InventarioController::class, 'productosMasVendidos']);
    });

    // ========================================
    // USUARIOS
    // ========================================
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UsuarioController::class, 'index']);
        Route::post('/', [UsuarioController::class, 'store']);
        Route::get('cajeros', [UsuarioController::class, 'cajeros']);
        Route::get('rol/{rol}', [UsuarioController::class, 'porRol']);
        Route::post('verificar-permiso', [UsuarioController::class, 'verificarPermiso']);
        Route::get('{id}', [UsuarioController::class, 'show']);
        Route::put('{id}', [UsuarioController::class, 'update']);
        Route::delete('{id}', [UsuarioController::class, 'destroy']);
        Route::post('{id}/activar', [UsuarioController::class, 'activar']);
        Route::post('{id}/cambiar-password', [UsuarioController::class, 'cambiarPassword']);
        Route::post('{id}/resetear-password', [UsuarioController::class, 'resetearPassword']);
        Route::get('{id}/estadisticas', [UsuarioController::class, 'estadisticas']);
    });

    // ========================================
    // CATÁLOGOS
    // ========================================
    Route::prefix('catalogos')->group(function () {
        Route::get('todos', [CatalogoController::class, 'todos']);
        Route::get('municipios', [CatalogoController::class, 'municipios']);
        Route::get('departamentos', [CatalogoController::class, 'departamentos']);
        Route::get('unidades-medida', [CatalogoController::class, 'unidadesMedida']);
        Route::get('tributos', [CatalogoController::class, 'tributos']);
        Route::get('tributos-cliente', [CatalogoController::class, 'tributosCliente']);
        Route::get('tipos-documento-identidad', [CatalogoController::class, 'tiposDocumentoIdentidad']);
        Route::get('tipos-organizacion', [CatalogoController::class, 'tiposOrganizacion']);
        Route::get('metodos-pago', [CatalogoController::class, 'metodosPago']);
        Route::get('formas-pago', [CatalogoController::class, 'formasPago']);
        Route::get('codigos-estandar', [CatalogoController::class, 'codigosEstandar']);
        Route::get('tipos-documento-factura', [CatalogoController::class, 'tiposDocumentoFactura']);
        Route::get('tipos-operacion', [CatalogoController::class, 'tiposOperacion']);
        Route::get('rangos-numeracion', [CatalogoController::class, 'rangosNumeracion']);
        Route::get('categorias', [CatalogoController::class, 'categorias']);
    });

    // ========================================
    // REPORTES
    // ========================================
    Route::prefix('reportes')->group(function () {
        Route::get('dashboard', [ReporteController::class, 'dashboard']);
        Route::get('ventas-por-periodo', [ReporteController::class, 'ventasPorPeriodo']);
        Route::get('ventas-por-usuario', [ReporteController::class, 'ventasPorUsuario']);
        Route::get('ventas-por-cliente', [ReporteController::class, 'ventasPorCliente']);
        Route::get('arqueos-caja', [ReporteController::class, 'arqueosCaja']);
    });
});