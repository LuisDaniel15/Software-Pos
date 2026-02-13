<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\ClienteService;
use Illuminate\Http\Request;
use Exception;

class ClienteController extends Controller
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    /**
     * Listar clientes
     */
    public function index(Request $request)
    {
        $query = Cliente::with(['tipoDocumento', 'municipio', 'tipoOrganizacion'])
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('tipo_persona')) {
            $query->where('tipo_persona', $request->tipo_persona);
        }

        if ($request->filled('search')) {
            $query->buscar($request->search);
        }

        $clientes = $query->paginate($request->per_page ?? 20);

        return response()->json($clientes);
    }

    /**
     * Crear cliente
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tipo_documento_id' => 'required|exists:tipos_documento_identidad,id',
                'numero_documento' => 'required|string|max:50',
                'dv' => 'nullable|integer',
                'tipo_persona' => 'required|in:natural,juridica',
                'razon_social' => 'nullable|string|max:255',
                'nombre_comercial' => 'nullable|string|max:255',
                'nombres' => 'nullable|string|max:255',
                'apellidos' => 'nullable|string|max:255',
                'graphic_representation_name' => 'nullable|string|max:255',
                'direccion' => 'nullable|string|max:255',
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'municipio_id' => 'nullable|exists:municipios,id',
                'tipo_organizacion_id' => 'required|exists:tipos_organizacion,id',
                'tributo_cliente_id' => 'required|exists:tributos_cliente,id',
            ]);

            $cliente = $this->clienteService->crearCliente($validated);

            return response()->json([
                'message' => 'Cliente creado exitosamente',
                'data' => $cliente->load(['tipoDocumento', 'municipio']),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al crear el cliente',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ver cliente
     */
    public function show(int $id)
    {
        $cliente = Cliente::with([
            'tipoDocumento',
            'municipio',
            'tipoOrganizacion',
            'tributoCliente'
        ])->findOrFail($id);

        return response()->json($cliente);
    }

    /**
     * Actualizar cliente
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'tipo_documento_id' => 'required|exists:tipos_documento_identidad,id',
                'numero_documento' => 'required|string|max:50',
                'dv' => 'nullable|integer',
                'tipo_persona' => 'required|in:natural,juridica',
                'razon_social' => 'nullable|string|max:255',
                'nombre_comercial' => 'nullable|string|max:255',
                'nombres' => 'nullable|string|max:255',
                'apellidos' => 'nullable|string|max:255',
                'graphic_representation_name' => 'nullable|string|max:255',
                'direccion' => 'nullable|string|max:255',
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'municipio_id' => 'nullable|exists:municipios,id',
                'tipo_organizacion_id' => 'required|exists:tipos_organizacion,id',
                'tributo_cliente_id' => 'required|exists:tributos_cliente,id',
                'activo' => 'nullable|boolean',
            ]);

            $cliente = $this->clienteService->actualizarCliente($id, $validated);

            return response()->json([
                'message' => 'Cliente actualizado exitosamente',
                'data' => $cliente,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el cliente',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Buscar clientes
     */
    public function buscar(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $clientes = $this->clienteService->buscarClientes(
            $request->q,
            $request->limit ?? 10
        );

        return response()->json($clientes);
    }

    /**
     * Obtener cliente por documento
     */
    public function porDocumento(Request $request)
    {
        $request->validate([
            'tipo_documento_id' => 'required|exists:tipos_documento_identidad,id',
            'numero_documento' => 'required|string',
        ]);

        $cliente = $this->clienteService->obtenerPorDocumento(
            $request->tipo_documento_id,
            $request->numero_documento
        );

        if (!$cliente) {
            return response()->json([
                'message' => 'Cliente no encontrado',
            ], 404);
        }

        return response()->json($cliente);
    }
}