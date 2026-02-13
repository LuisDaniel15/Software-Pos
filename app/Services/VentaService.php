<?php

namespace App\Services;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\RangoNumeracion;
use App\Models\MovimientoCaja;
use App\Models\RetencionVenta;
use App\Models\RetencionDetalleVenta;
use App\Models\DescuentoRecargoVenta;
use App\Models\LogIntegracionFactus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class VentaService
{
    protected FactusService $factusService;
    protected InventarioService $inventarioService;

    public function __construct(
        FactusService $factusService,
        InventarioService $inventarioService
    ) {
        $this->factusService = $factusService;
        $this->inventarioService = $inventarioService;
    }

    /**
     * Crear una venta completa con facturación electrónica
     */
    public function crearVenta(array $datos): Venta
    {
        return DB::transaction(function () use ($datos) {
            // 1. Validaciones previas
            $this->validarDatosVenta($datos);

            // 2. Validar stock de productos
            $this->validarStockProductos($datos['items']);

            // 3. Obtener rango de numeración activo
            $rango = $this->obtenerRangoActivo($datos['tipo_documento'] ?? 'Factura de Venta');

            // 4. Crear venta
            $venta = $this->crearVentaDB($datos, $rango);

            // 5. Crear detalles de venta
            $this->crearDetallesVenta($venta, $datos['items']);

            // 6. Crear descuentos/recargos si existen
            if (!empty($datos['descuentos_recargos'])) {
                $this->crearDescuentosRecargos($venta, $datos['descuentos_recargos']);
            }

            // 7. Recalcular totales de la venta
            $this->recalcularTotalesVenta($venta);

            // 8. Actualizar stock de productos
            $this->inventarioService->descontarStockVenta($venta);

            // 9. Registrar movimiento de caja
            if (!empty($datos['turno_caja_id'])) {
                $this->registrarMovimientoCaja($venta, $datos);
            }

            // 10. Enviar a Factus para facturación electrónica
            if ($datos['facturar_electronica'] ?? true) {
                $this->facturarElectronicamente($venta);
            }

            return $venta->fresh(['detalles', 'cliente', 'usuario']);
        });
    }

    /**
     * Validar datos básicos de la venta
     */
    protected function validarDatosVenta(array $datos): void
    {
        if (empty($datos['cliente_id'])) {
            throw new Exception('Debe especificar un cliente');
        }

        if (empty($datos['items']) || !is_array($datos['items'])) {
            throw new Exception('La venta debe tener al menos un producto');
        }

        if (empty($datos['usuario_id'])) {
            throw new Exception('Debe especificar el usuario que realiza la venta');
        }

        // Validar que el cliente existe y está activo
        $cliente = Cliente::find($datos['cliente_id']);
        if (!$cliente || !$cliente->activo) {
            throw new Exception('Cliente no válido o inactivo');
        }

        // Si es pago a crédito, validar fecha de vencimiento
        if (($datos['forma_pago_id'] ?? null) == 2 && empty($datos['fecha_vencimiento'])) {
            throw new Exception('Para pago a crédito debe especificar fecha de vencimiento');
        }
    }

    /**
     * Validar que hay stock suficiente para todos los productos
     */
    protected function validarStockProductos(array $items): void
    {
        foreach ($items as $item) {
            $producto = Producto::find($item['producto_id']);
            
            if (!$producto) {
                throw new Exception("Producto {$item['producto_id']} no encontrado");
            }

            if (!$producto->activo) {
                throw new Exception("El producto {$producto->nombre} está inactivo");
            }

            if (!$producto->tieneStockDisponible($item['cantidad'])) {
                throw new Exception(
                    "Stock insuficiente para {$producto->nombre}. " .
                    "Disponible: {$producto->stock_actual}, Solicitado: {$item['cantidad']}"
                );
            }
        }
    }

    /**
     * Obtener rango de numeración activo y disponible
     */
    protected function obtenerRangoActivo(string $tipoDocumento): RangoNumeracion
    {
        $rango = RangoNumeracion::where('document', $tipoDocumento)
            ->where('is_active', 1)
            ->where('is_expired', false)
            ->whereNotNull('desde')
            ->whereNotNull('hasta')
            ->whereRaw('consecutivo_actual < hasta')
            ->first();

        if (!$rango) {
            throw new Exception("No hay rangos de numeración disponibles para {$tipoDocumento}");
        }

        if (!$rango->estaVigente()) {
            throw new Exception("El rango de numeración no está vigente");
        }

        return $rango;
    }

    /**
     * Crear registro de venta en BD
     */
    protected function crearVentaDB(array $datos, RangoNumeracion $rango): Venta
    {
        $numeroVenta = $this->generarNumeroVenta();
        $referenceCode = $this->generarReferenceCode();

        return Venta::create([
            'turno_caja_id' => $datos['turno_caja_id'] ?? null,
            'cliente_id' => $datos['cliente_id'],
            'usuario_id' => $datos['usuario_id'],
            'establecimiento_id' => $datos['establecimiento_id'] ?? null,
            'numero_venta' => $numeroVenta,
            'reference_code' => $referenceCode,
            'tipo_documento_id' => $datos['tipo_documento_id'] ?? 1, // 1 = Factura
            'rango_numeracion_id' => $rango->id,
            'fecha_venta' => now(),
            'forma_pago_id' => $datos['forma_pago_id'] ?? 1, // 1 = Contado
            'fecha_vencimiento' => $datos['fecha_vencimiento'] ?? null,
            'metodo_pago_id' => $datos['metodo_pago_id'] ?? 1, // 1 = Efectivo
            'tipo_operacion_id' => $datos['tipo_operacion_id'] ?? 1, // 1 = Estándar
            'orden_numero' => $datos['orden_numero'] ?? null,
            'orden_fecha' => $datos['orden_fecha'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'enviar_email' => $datos['enviar_email'] ?? true,
            'estado' => 'pendiente',
            'estado_dian' => 'pendiente',
            'subtotal' => 0,
            'total_iva' => 0,
            'total_descuentos' => 0,
            'total_recargos' => 0,
            'total' => 0,
        ]);
    }

    /**
     * Crear detalles de venta (items)
     */
    protected function crearDetallesVenta(Venta $venta, array $items): void
    {
        foreach ($items as $item) {
            $producto = Producto::findOrFail($item['producto_id']);
            
            // Calcular totales del item
            $totales = DetalleVenta::calcularTotales(
                $item['cantidad'],
                $producto->precio_venta,
                $producto->porcentaje_iva,
                $item['porcentaje_descuento'] ?? 0
            );

            // Crear detalle
            $detalle = DetalleVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'codigo_referencia' => $producto->codigo_referencia,
                'nombre_producto' => $producto->nombre,
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $producto->precio_venta,
                'porcentaje_iva' => $producto->porcentaje_iva,
                'porcentaje_descuento' => $item['porcentaje_descuento'] ?? 0,
                'precio_base' => $totales['precio_base'],
                'descuento' => $totales['descuento'],
                'subtotal' => $totales['subtotal'],
                'total_iva' => $totales['total_iva'],
                'total' => $totales['total'],
                'unidad_medida_id' => $producto->unidad_medida_id,
                'codigo_estandar_id' => $producto->codigo_estandar_id,
                'tributo_id' => $producto->tributo_id,
                'es_excluido' => $producto->es_excluido,
                'nota_item' => $item['nota'] ?? null,
            ]);

            // Crear retenciones si aplican
            if (!empty($item['retenciones'])) {
                $this->crearRetencionesDetalle($detalle, $item['retenciones']);
            }
        }
    }

    /**
     * Crear retenciones a nivel de detalle
     */
    protected function crearRetencionesDetalle(DetalleVenta $detalle, array $retenciones): void
    {
        foreach ($retenciones as $retencion) {
            $valor = $detalle->subtotal * ($retencion['porcentaje'] / 100);

            RetencionDetalleVenta::create([
                'detalle_venta_id' => $detalle->id,
                'codigo_retencion' => $retencion['codigo'],
                'nombre_retencion' => $retencion['nombre'],
                'porcentaje' => $retencion['porcentaje'],
                'valor' => $valor,
            ]);
        }
    }

    /**
     * Crear descuentos/recargos a nivel de venta
     */
    protected function crearDescuentosRecargos(Venta $venta, array $descuentosRecargos): void
    {
        foreach ($descuentosRecargos as $dr) {
            DescuentoRecargoVenta::create([
                'venta_id' => $venta->id,
                'codigo_concepto' => $dr['codigo_concepto'] ?? '03',
                'es_recargo' => $dr['es_recargo'] ?? false,
                'razon' => $dr['razon'],
                'base' => $dr['base'],
                'porcentaje' => $dr['porcentaje'] ?? null,
                'monto' => $dr['monto'],
            ]);
        }
    }

    /**
     * Recalcular totales de la venta
     */
    protected function recalcularTotalesVenta(Venta $venta): void
    {
        $subtotal = $venta->detalles->sum('subtotal');
        $totalIva = $venta->detalles->sum('total_iva');
        $totalDescuentos = $venta->descuentosRecargos()
            ->where('es_recargo', false)
            ->sum('monto');
        $totalRecargos = $venta->descuentosRecargos()
            ->where('es_recargo', true)
            ->sum('monto');

        $total = $subtotal + $totalIva - $totalDescuentos + $totalRecargos;

        $venta->update([
            'subtotal' => $subtotal,
            'total_iva' => $totalIva,
            'total_descuentos' => $totalDescuentos,
            'total_recargos' => $totalRecargos,
            'total' => $total,
        ]);

        // Consolidar retenciones
        $this->consolidarRetenciones($venta);
    }

    /**
     * Consolidar retenciones a nivel de venta
     */
    protected function consolidarRetenciones(Venta $venta): void
    {
        // Eliminar retenciones consolidadas anteriores
        $venta->retenciones()->delete();

        // Agrupar retenciones por código
        $retencionesAgrupadas = [];
        
        foreach ($venta->detalles as $detalle) {
            foreach ($detalle->retenciones as $retencion) {
                $codigo = $retencion->codigo_retencion;
                
                if (!isset($retencionesAgrupadas[$codigo])) {
                    $retencionesAgrupadas[$codigo] = [
                        'codigo' => $codigo,
                        'nombre' => $retencion->nombre_retencion,
                        'valor' => 0,
                    ];
                }
                
                $retencionesAgrupadas[$codigo]['valor'] += $retencion->valor;
            }
        }

        // Crear retenciones consolidadas
        foreach ($retencionesAgrupadas as $retencion) {
            RetencionVenta::create([
                'venta_id' => $venta->id,
                'codigo_tributo' => $retencion['codigo'],
                'nombre_retencion' => $retencion['nombre'],
                'valor_total' => $retencion['valor'],
            ]);
        }
    }

    /**
     * Registrar movimiento de caja
     */
    protected function registrarMovimientoCaja(Venta $venta, array $datos): void
    {
        MovimientoCaja::registrarMovimientoVenta(
            $datos['turno_caja_id'],
            $venta->id,
            $venta->total,
            $datos['metodo_pago_id'] ?? 1,
            $datos['usuario_id']
        );
    }

    /**
     * Facturar electrónicamente con Factus
     */
    protected function facturarElectronicamente(Venta $venta): void
    {
        try {
            // Construir payload para Factus
            $payload = $this->construirPayloadFactus($venta);

            // Enviar a Factus
            $response = $this->factusService->crearFactura($payload);

            if ($response->successful()) {
                $data = $response->json();
                
                // Actualizar venta con datos de Factus
                $this->actualizarVentaConRespuestaFactus($venta, $data);

                // Incrementar consecutivo del rango
                $venta->rangoNumeracion->incrementarConsecutivo();

                // Log exitoso
                LogIntegracionFactus::registrar(
                    'crear_factura',
                    $payload,
                    $data,
                    $response->status(),
                    true,
                    $venta->id,
                    $venta->usuario_id
                );
            } else {
                // Error en Factus
                $this->manejarErrorFactus($venta, $response, $payload);
            }
        } catch (Exception $e) {
            Log::error('Error al facturar electrónicamente', [
                'venta_id' => $venta->id,
                'error' => $e->getMessage(),
            ]);

            $venta->update([
                'estado_dian' => 'rechazada',
                'errores_dian' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        }
    }

    /**
     * Construir payload para Factus API
     */
    protected function construirPayloadFactus(Venta $venta): array
    {
        $cliente = $venta->cliente;
        $items = [];

        foreach ($venta->detalles as $detalle) {
            $item = [
                'code_reference' => $detalle->codigo_referencia,
                'name' => $detalle->nombre_producto,
                'quantity' => $detalle->cantidad,
                'price' => (float) $detalle->precio_unitario,
                'tax_rate' => $detalle->porcentaje_iva,
                'discount_rate' => (float) $detalle->porcentaje_descuento,
                'unit_measure_id' => $detalle->unidad_medida_id,
                'standard_code_id' => $detalle->codigo_estandar_id,
                'is_excluded' => $detalle->es_excluido,
                'tribute_id' => $detalle->tributo_id,
            ];

            // Agregar retenciones si existen
            if ($detalle->retenciones->isNotEmpty()) {
                $item['withholding_taxes'] = [];
                
                foreach ($detalle->retenciones as $retencion) {
                    $item['withholding_taxes'][] = [
                        'code' => $retencion->codigo_retencion,
                        'withholding_tax_rate' => (float) $retencion->porcentaje,
                    ];
                }
            }

            $items[] = $item;
        }

        $payload = [
            'numbering_range_id' => $venta->rango_numeracion_id,
            'reference_code' => $venta->reference_code,
            'observation' => $venta->observaciones,
            'payment_form' => (string) $venta->forma_pago_id,
            'payment_method_code' => (int) $venta->metodo_pago_id,
            'operation_type' => (string) $venta->tipo_operacion_id,
            'send_email' => $venta->enviar_email,
            'customer' => $cliente->getDatosParaFactus(),
            'items' => $items,
        ];

        // Agregar fecha de vencimiento si es crédito
        if ($venta->fecha_vencimiento) {
            $payload['payment_due_date'] = $venta->fecha_vencimiento->format('Y-m-d');
        }

        // Agregar descuentos/recargos
        if ($venta->descuentosRecargos->isNotEmpty()) {
            $payload['allowance_charges'] = [];
            
            foreach ($venta->descuentosRecargos as $dr) {
                $payload['allowance_charges'][] = [
                    'concept_type' => $dr->codigo_concepto,
                    'is_surcharge' => $dr->es_recargo,
                    'reason' => $dr->razon,
                    'base_amount' => (float) $dr->base,
                    'amount' => (float) $dr->monto,
                ];
            }
        }

        // Agregar establecimiento si existe
        if ($venta->establecimiento) {
            $payload['establishment'] = $venta->establecimiento->getDatosParaFactus();
        }

        return $payload;
    }

    /**
     * Actualizar venta con respuesta exitosa de Factus
     */
    protected function actualizarVentaConRespuestaFactus(Venta $venta, array $data): void
    {
        $bill = $data['data']['bill'] ?? [];

        $venta->update([
            'numero_factura_dian' => $bill['number'] ?? null,
            'cufe' => $bill['cufe'] ?? null,
            'qr_url' => $bill['qr'] ?? null,
            'qr_image' => $bill['qr_image'] ?? null,
            'estado_dian' => 'validada',
            'fecha_validacion_dian' => now(),
            'respuesta_factus' => $data,
            'estado' => 'completada',
            'gross_value' => $bill['gross_value'] ?? null,
            'taxable_amount' => $bill['taxable_amount'] ?? null,
        ]);
    }

    /**
     * Manejar error de Factus
     */
    protected function manejarErrorFactus(Venta $venta, $response, array $payload): void
    {
        $data = $response->json();
        
        $venta->update([
            'estado_dian' => 'rechazada',
            'errores_dian' => $data,
            'respuesta_factus' => $data,
        ]);

        // Log de error
        LogIntegracionFactus::registrar(
            'crear_factura',
            $payload,
            $data,
            $response->status(),
            false,
            $venta->id,
            $venta->usuario_id
        );

        throw new Exception(
            $data['message'] ?? 'Error al validar factura con Factus'
        );
    }

    /**
     * Generar número de venta interno
     */
    protected function generarNumeroVenta(): string
    {
        $ultima = Venta::latest('id')->first();
        $siguiente = $ultima ? $ultima->id + 1 : 1;
        
        return 'VTA-' . str_pad($siguiente, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Generar reference_code único para Factus
     */
    protected function generarReferenceCode(): string
    {
        return 'REF-' . now()->format('Ymd-His') . '-' . uniqid();
    }

    /**
     * Reintentar facturación de una venta rechazada
     */
    public function reintentarFacturacion(Venta $venta): Venta
    {
        if (!$venta->puedeReintentarFacturacion()) {
            throw new Exception('Esta venta no puede reintentarse');
        }

        $this->facturarElectronicamente($venta);

        return $venta->fresh();
    }
}