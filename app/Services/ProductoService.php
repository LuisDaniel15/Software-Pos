<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductoService
{
    /**
     * Crear producto
     */
    public function crearProducto(array $datos): Producto
    {
        return DB::transaction(function () use ($datos) {
            // Validaciones
            $this->validarDatosProducto($datos);

            // Verificar código duplicado
            $this->verificarCodigoDuplicado($datos['codigo_referencia']);

            // Calcular precio sin IVA si viene con IVA
            if (!empty($datos['precio_incluye_iva']) && $datos['precio_incluye_iva']) {
                // El precio ya viene con IVA, no hacer nada
            }

            return Producto::create($datos);
        });
    }

    /**
     * Actualizar producto
     */
    public function actualizarProducto(int $id, array $datos): Producto
    {
        return DB::transaction(function () use ($id, $datos) {
            $producto = Producto::findOrFail($id);

            // Validaciones
            $this->validarDatosProducto($datos, $id);

            // Verificar código duplicado
            if ($datos['codigo_referencia'] != $producto->codigo_referencia) {
                $this->verificarCodigoDuplicado($datos['codigo_referencia'], $id);
            }

            $producto->update($datos);

            return $producto->fresh();
        });
    }

    /**
     * Validar datos del producto
     */
    protected function validarDatosProducto(array $datos, ?int $productoId = null): void
    {
        if (empty($datos['nombre'])) {
            throw new Exception('El nombre del producto es obligatorio');
        }

        if (empty($datos['codigo_referencia'])) {
            throw new Exception('El código de referencia es obligatorio');
        }

        if (empty($datos['precio_venta']) || $datos['precio_venta'] <= 0) {
            throw new Exception('El precio de venta debe ser mayor a 0');
        }

        if (empty($datos['unidad_medida_id'])) {
            throw new Exception('Debe especificar la unidad de medida');
        }

        if (empty($datos['tributo_id'])) {
            throw new Exception('Debe especificar el tributo');
        }
    }

    /**
     * Verificar código duplicado
     */
    protected function verificarCodigoDuplicado(string $codigo, ?int $productoId = null): void
    {
        $query = Producto::where('codigo_referencia', $codigo);

        if ($productoId) {
            $query->where('id', '!=', $productoId);
        }

        if ($query->exists()) {
            throw new Exception('Ya existe un producto con este código');
        }
    }

    /**
     * Buscar productos
     */
    public function buscarProductos(string $busqueda, int $limit = 20, bool $soloActivos = true)
    {
        $query = Producto::buscar($busqueda)
            ->with(['categoria', 'unidadMedida', 'tributo']);

        if ($soloActivos) {
            $query->activos();
        }

        return $query->limit($limit)->get();
    }

    /**
     * Obtener productos con stock bajo
     */
    public function obtenerProductosBajoStock()
    {
        return Producto::bajoStock()
            ->activos()
            ->with(['categoria', 'unidadMedida'])
            ->orderBy('stock_actual')
            ->get();
    }

    /**
     * Calcular precio con IVA
     */
    public function calcularPrecioConIva(float $precioBase, string $porcentajeIva): float
    {
        $porcentaje = (float) str_replace(',', '.', $porcentajeIva);
        return $precioBase * (1 + ($porcentaje / 100));
    }

    /**
     * Calcular precio sin IVA
     */
    public function calcularPrecioSinIva(float $precioConIva, string $porcentajeIva): float
    {
        $porcentaje = (float) str_replace(',', '.', $porcentajeIva);
        return $precioConIva / (1 + ($porcentaje / 100));
    }
}