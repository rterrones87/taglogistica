<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Validator;

class RolePermissionController extends Controller
{
    /**
     * Listar todos los roles
     */
    public function index()
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    /**
     * Obtener permisos de un rol específico
     */
    public function show($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        
        return response()->json([
            'role' => $role,
            'permission_ids' => $role->permissions->pluck('id')->toArray()
        ]);
    }

    /**
     * Actualizar permisos de un rol
     */
    public function update(Request $request, $roleId)
    {
        $validator = Validator::make($request->all(), [
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id'
        ], [
            'permission_ids.required' => 'Debe seleccionar al menos un permiso',
            'permission_ids.array' => 'Los permisos deben ser un array',
            'permission_ids.*.exists' => 'Uno o más permisos no existen'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validación fallida',
                'messages' => $validator->errors()
            ], 422);
        }

        $role = Role::findOrFail($roleId);
        
        // Sincronizar permisos (elimina los no incluidos y agrega nuevos)
        $role->permissions()->sync($request->permission_ids);

        return response()->json([
            'message' => 'Permisos actualizados correctamente',
            'role' => $role->load('permissions')
        ]);
    }

    /**
     * Crear un nuevo rol
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name'
        ], [
            'name.required' => 'El nombre del rol es obligatorio',
            'name.string' => 'El nombre debe ser una cadena de texto',
            'name.max' => 'El nombre no puede exceder 255 caracteres',
            'name.unique' => 'Ya existe un rol con ese nombre'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validación fallida',
                'messages' => $validator->errors()
            ], 422);
        }

        $role = Role::create([
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Rol creado correctamente',
            'role' => $role
        ], 201);
    }

    /**
     * Listar todos los permisos disponibles
     */
    public function permissions()
    {
        $permissions = Permission::orderBy('name')->get();
        return response()->json($permissions);
    }
}
