<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Spatie\Permission\Models\Permission;
use Illuminate\Routing\Controller as BaseController;

class PermissionController extends BaseController
{
    public function __construct()
    {
        $this->middleware('check.permission:Super Admin')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $permissions = Permission::orderBy('id', 'DESC')->get();
        return response()->json(['permissions' => $permissions], 200);
    }

    public function create()
    {
        // Implementar lógica para la creación de un nuevo permiso (opcional)
    }

    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|unique:permissions,name|max:255', // Agregar reglas de validación necesarias
    ]);

    try {
        $permission = Permission::create([
            'name' => $request->input('name'),
        ]);

        return response()->json(['message' => 'Permission created successfully'], 201);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to create permission'], 500);
    }
}

    public function show($id)
{
    try {
        $permission = Permission::findOrFail($id);
        return response()->json(['permission' => $permission], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Permission not found'], 404);
    }
}

    public function edit($id)
    {
        // Implementar lógica para editar un permiso (opcional)
    }

    public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|unique:permissions,name,' . $id . '|max:255', // Agregar reglas de validación necesarias
    ]);

    try {
        $permission = Permission::findOrFail($id);
        $permission->name = $request->input('name');
        $permission->save();

        return response()->json(['message' => 'Permission updated successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to update permission'], 500);
    }
}

    public function destroy($id)
{
    try {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json(['message' => 'Permission deleted successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to delete permission'], 500);
    }
}
}
