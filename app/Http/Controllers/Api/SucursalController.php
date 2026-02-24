<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    /**
     * Listar sucursales activas
     */
    public function index(Request $request)
    {
        $query = Sucursal::with(['empresa', 'municipio'])
            ->activas();

        if ($request->filled('empresa_id')) {
            $query->where('empresa_id', $request->empresa_id);
        }

        $sucursales = $query->orderBy('nombre')->get();

        return response()->json([
            'data' => $sucursales
        ]);
    }

    /**
     * Obtener sucursal del usuario autenticado
     */
    public function sucursalActual(Request $request)
    {
        $user = $request->user();
        
        if (!$user->sucursal_id) {
            return response()->json([
                'message' => 'Usuario no tiene sucursal asignada',
                'sucursal' => null
            ]);
        }

        $sucursal = Sucursal::with(['empresa', 'municipio'])
            ->find($user->sucursal_id);

        return response()->json([
            'sucursal' => $sucursal
        ]);
    }

    /**
     * Obtener una sucursal específica
     */
    public function show(int $id)
    {
        $sucursal = Sucursal::with(['empresa', 'municipio'])
            ->findOrFail($id);

        return response()->json($sucursal);
    }
}