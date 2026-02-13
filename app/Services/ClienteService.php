<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Exception;

class ClienteService
{
    /**
     * Crear cliente
     */
    public function crearCliente(array $datos): Cliente
    {
        return DB::transaction(function () use ($datos) {
            // Validaciones
            $this->validarDatosCliente($datos);

            // Verificar duplicados
            $this->verificarDuplicado($datos);

            // Calcular DV si es NIT
            if ($datos['tipo_documento_id'] == 6 && empty($datos['dv'])) {
                $datos['dv'] = $this->calcularDigitoVerificacion($datos['numero_documento']);
            }

            // Generar graphic_representation_name si no existe
            if (empty($datos['graphic_representation_name'])) {
                $datos['graphic_representation_name'] = $this->generarNombreRepresentacion($datos);
            }

            return Cliente::create($datos);
        });
    }

    /**
     * Actualizar cliente
     */
    public function actualizarCliente(int $id, array $datos): Cliente
    {
        return DB::transaction(function () use ($id, $datos) {
            $cliente = Cliente::findOrFail($id);

            // Validaciones
            $this->validarDatosCliente($datos, $id);

            // Verificar duplicados (excluyendo el cliente actual)
            $this->verificarDuplicado($datos, $id);

            // Recalcular DV si cambió el NIT
            if ($datos['tipo_documento_id'] == 6 && 
                $datos['numero_documento'] != $cliente->numero_documento) {
                $datos['dv'] = $this->calcularDigitoVerificacion($datos['numero_documento']);
            }

            $cliente->update($datos);

            return $cliente->fresh();
        });
    }

    /**
     * Validar datos del cliente
     */
    protected function validarDatosCliente(array $datos, ?int $clienteId = null): void
    {
        // Validar tipo de persona vs datos requeridos
        if ($datos['tipo_persona'] === 'juridica' && empty($datos['razon_social'])) {
            throw new Exception('La razón social es obligatoria para personas jurídicas');
        }

        if ($datos['tipo_persona'] === 'natural' && empty($datos['nombres'])) {
            throw new Exception('El nombre es obligatorio para personas naturales');
        }

        // Validar DV para NIT
        if ($datos['tipo_documento_id'] == 6 && empty($datos['dv'])) {
            // Se calculará automáticamente
        }

        // Validar email si se proporciona
        if (!empty($datos['email']) && !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('El email no es válido');
        }
    }

    /**
     * Verificar que no exista un cliente duplicado
     */
    protected function verificarDuplicado(array $datos, ?int $clienteId = null): void
    {
        $query = Cliente::where('tipo_documento_id', $datos['tipo_documento_id'])
            ->where('numero_documento', $datos['numero_documento']);

        if ($clienteId) {
            $query->where('id', '!=', $clienteId);
        }

        if ($query->exists()) {
            throw new Exception('Ya existe un cliente con este documento');
        }
    }

    /**
     * Calcular dígito de verificación para NIT
     */
    protected function calcularDigitoVerificacion(string $nit): int
    {
        $vpri = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $z = strlen($nit);
        $x = 0;

        for ($i = 0; $i < $z; $i++) {
            $y = (int) substr($nit, $i, 1);
            $x += ($y * $vpri[$z - $i - 1]);
        }

        $y = $x % 11;

        return ($y > 1) ? 11 - $y : $y;
    }

    /**
     * Generar nombre de representación gráfica
     */
    protected function generarNombreRepresentacion(array $datos): string
    {
        if ($datos['tipo_persona'] === 'juridica') {
            return $datos['razon_social'];
        }

        return trim(($datos['nombres'] ?? '') . ' ' . ($datos['apellidos'] ?? ''));
    }

    /**
     * Buscar clientes
     */
    public function buscarClientes(string $busqueda, int $limit = 10)
    {
        return Cliente::activos()
            ->buscar($busqueda)
            ->with(['tipoDocumento', 'municipio'])
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener cliente por documento
     */
    public function obtenerPorDocumento(int $tipoDocumentoId, string $numeroDocumento): ?Cliente
    {
        return Cliente::where('tipo_documento_id', $tipoDocumentoId)
            ->where('numero_documento', $numeroDocumento)
            ->first();
    }
}