<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Exception;

class UsuarioController extends Controller
{
    /**
     * Listar usuarios
     */
    public function index(Request $request)
    {
        $query = User::query()->orderBy('nombre');

        // Filtros
        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('rol')) {
            $query->where('rol', $request->rol);
        }

        if ($request->filled('search')) {
            $query->buscar($request->search);
        }

        $usuarios = $query->paginate($request->per_page ?? 20);

        // Ocultar passwords
        $usuarios->makeHidden(['password', 'remember_token']);

        return response()->json($usuarios);
    }

    /**
     * Crear usuario
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'rol' => ['required', Rule::in(['admin', 'cajero', 'supervisor'])],
                'activo' => 'nullable|boolean',
            ]);

            $usuario = User::create([
                'nombre' => $validated['nombre'],
                'name' => $validated['nombre'], // Laravel espera 'name'
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'rol' => $validated['rol'],
                'activo' => $validated['activo'] ?? true,
            ]);

            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'data' => $usuario->makeHidden(['password', 'remember_token']),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al crear el usuario',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Ver usuario
     */
    public function show(int $id)
    {
        $usuario = User::findOrFail($id);

        return response()->json(
            $usuario->makeHidden(['password', 'remember_token'])
        );
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, int $id)
    {
        try {
            $usuario = User::findOrFail($id);

            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($id)
                ],
                'password' => 'nullable|string|min:6|confirmed',
                'rol' => ['required', Rule::in(['admin', 'cajero', 'supervisor'])],
                'activo' => 'nullable|boolean',
            ]);

            $datosActualizar = [
                'nombre' => $validated['nombre'],
                'name' => $validated['nombre'],
                'email' => $validated['email'],
                'rol' => $validated['rol'],
            ];

            // Solo actualizar password si se proporcionó
            if (!empty($validated['password'])) {
                $datosActualizar['password'] = Hash::make($validated['password']);
            }

            // Solo actualizar activo si se proporcionó
            if (isset($validated['activo'])) {
                $datosActualizar['activo'] = $validated['activo'];
            }

            $usuario->update($datosActualizar);

            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'data' => $usuario->fresh()->makeHidden(['password', 'remember_token']),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el usuario',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Eliminar (desactivar) usuario
     */
    public function destroy(int $id)
    {
        try {
            $usuario = User::findOrFail($id);

            // No permitir eliminar al usuario autenticado
            if ($usuario->id === auth()->id()) {
                return response()->json([
                    'message' => 'No puedes desactivar tu propio usuario',
                ], 400);
            }

            // Desactivar en lugar de eliminar
            $usuario->update(['activo' => false]);

            return response()->json([
                'message' => 'Usuario desactivado exitosamente',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al desactivar el usuario',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Activar usuario
     */
    public function activar(int $id)
    {
        try {
            $usuario = User::findOrFail($id);

            $usuario->update(['activo' => true]);

            return response()->json([
                'message' => 'Usuario activado exitosamente',
                'data' => $usuario->fresh()->makeHidden(['password', 'remember_token']),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al activar el usuario',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarPassword(Request $request, int $id)
    {
        try {
            $usuario = User::findOrFail($id);

            $validated = $request->validate([
                'password_actual' => 'required|string',
                'password_nuevo' => 'required|string|min:6|confirmed',
            ]);

            // Verificar password actual
            if (!Hash::check($validated['password_actual'], $usuario->password)) {
                return response()->json([
                    'message' => 'La contraseña actual es incorrecta',
                ], 400);
            }

            // Actualizar password
            $usuario->update([
                'password' => Hash::make($validated['password_nuevo']),
            ]);

            return response()->json([
                'message' => 'Contraseña actualizada exitosamente',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar la contraseña',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener usuarios por rol
     */
    public function porRol(string $rol)
    {
        if (!in_array($rol, ['admin', 'cajero', 'supervisor'])) {
            return response()->json([
                'message' => 'Rol no válido',
            ], 400);
        }

        $usuarios = User::where('rol', $rol)
            ->activos()
            ->orderBy('nombre')
            ->get()
            ->makeHidden(['password', 'remember_token']);

        return response()->json($usuarios);
    }

    /**
     * Obtener cajeros activos
     */
    public function cajeros()
    {
        $cajeros = User::cajeros()
            ->activos()
            ->orderBy('nombre')
            ->get()
            ->makeHidden(['password', 'remember_token']);

        return response()->json($cajeros);
    }

    /**
     * Verificar permisos del usuario
     */
    public function verificarPermiso(Request $request)
    {
        $request->validate([
            'permiso' => 'required|string',
        ]);

        $usuario = $request->user();
        $tienePermiso = $usuario->puedeAcceder($request->permiso);

        return response()->json([
            'tiene_permiso' => $tienePermiso,
        ]);
    }

    /**
     * Obtener estadísticas del usuario
     */
    public function estadisticas(int $id, Request $request)
    {
        $usuario = User::findOrFail($id);

        $desde = $request->desde ?? now()->startOfMonth();
        $hasta = $request->hasta ?? now()->endOfMonth();

        $ventas = $usuario->ventas()
            ->whereBetween('fecha_venta', [$desde, $hasta])
            ->where('estado', 'completada')
            ->get();

        $turnos = $usuario->turnosCaja()
            ->whereBetween('fecha_apertura', [$desde, $hasta])
            ->get();

        return response()->json([
            'usuario' => $usuario->makeHidden(['password', 'remember_token']),
            'estadisticas' => [
                'total_ventas' => $ventas->count(),
                'monto_total_vendido' => $ventas->sum('total'),
                'ticket_promedio' => $ventas->count() > 0 
                    ? $ventas->sum('total') / $ventas->count() 
                    : 0,
                'total_turnos' => $turnos->count(),
                'turnos_abiertos' => $turnos->where('estado', 'abierto')->count(),
                'turnos_cerrados' => $turnos->where('estado', 'cerrado')->count(),
            ],
        ]);
    }

    /**
     * Resetear contraseña (solo admin)
     */
    public function resetearPassword(Request $request, int $id)
    {
        try {
            // Verificar que el usuario autenticado sea admin
            if (!$request->user()->es_admin) {
                return response()->json([
                    'message' => 'No tienes permisos para realizar esta acción',
                ], 403);
            }

            $usuario = User::findOrFail($id);

            $validated = $request->validate([
                'password_nuevo' => 'required|string|min:6|confirmed',
            ]);

            $usuario->update([
                'password' => Hash::make($validated['password_nuevo']),
            ]);

            return response()->json([
                'message' => 'Contraseña reseteada exitosamente',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al resetear la contraseña',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}