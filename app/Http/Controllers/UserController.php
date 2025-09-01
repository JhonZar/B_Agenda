<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Response;


class UserController extends Controller
{
    public function profesores(Request $request)
    {
        $query = User::where('role', 'profesor');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('name', 'like', "%{$q}%");
        }

        return $query->get([
            'id', 'name', 'email', 'phone', 'ci', 'created_at', 'updated_at'
        ]);
    }
    public function storeProfesor(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'ci'    => 'required|string|unique:users,ci|max:20',
        ]);

        // Forzamos el role y ciframos la contraseña con el CI
        $data['role']     = 'profesor';
        $data['password'] = bcrypt($data['ci']);

        $profesor = User::create($data);

        // Devolvemos sólo los campos necesarios al front
        return response()->json([
            'id'         => $profesor->id,
            'name'       => $profesor->name,
            'email'      => $profesor->email,
            'phone'      => $profesor->phone,
            'ci'         => $profesor->ci,
            'created_at' => $profesor->created_at,
            'updated_at' => $profesor->updated_at,
        ], 201);
    }

    public function indexEstudiante(Request $request)
    {
        $query = User::where('role', 'estudiante');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('name', 'like', "%{$q}%");
        }

        return $query->get([
            'id', 'name', 'email', 'phone', 'ci', 'created_at', 'updated_at'
        ]);
    }

    /**
     * POST /api/estudiantes
     */
    public function storeEstudiante(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'ci'    => 'required|string|unique:users,ci|max:20',
        ]);

        $data['role']     = 'estudiante';
        // Si quieres usar CI como contraseña:
        $data['password'] = bcrypt($data['ci']);

        $estudiante = User::create($data);

        return response()->json([
            'id'         => $estudiante->id,
            'name'       => $estudiante->name,
            'email'      => $estudiante->email,
            'phone'      => $estudiante->phone,
            'ci'         => $estudiante->ci,
            'created_at' => $estudiante->created_at,
            'updated_at' => $estudiante->updated_at,
        ], Response::HTTP_CREATED);
    }

    public function storePadre(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'ci'    => 'required|string|unique:users,ci|max:20',
        ]);

        $data['role']     = 'padre';
        $data['password'] = bcrypt($data['ci']);

        $padre = User::create($data);

        return response()->json([
            'id'         => $padre->id,
            'name'       => $padre->name,
            'email'      => $padre->email,
            'phone'      => $padre->phone,
            'ci'         => $padre->ci,
            'created_at' => $padre->created_at,
            'updated_at' => $padre->updated_at,
        ], Response::HTTP_CREATED);
    }

    public function indexPadre(Request $request)
    {
        $query = User::where('role', 'padre');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('name', 'like', "%{$q}%");
        }

        return $query->get([
            'id', 'name', 'email', 'phone', 'ci', 'created_at', 'updated_at'
        ]);
    }

    public function updatePadre(Request $request, $id)
    {
        $padre = User::where('role', 'padre')->findOrFail($id);

        $data = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'email' => "sometimes|nullable|email|unique:users,email,{$id}",
            'phone' => 'nullable|string|max:20',
            'ci'    => "sometimes|required|string|unique:users,ci,{$id}|max:20",
        ]);

        $padre->update($data);

        return response()->json($padre);
    }

    /**
     * GET /api/estudiantes/{id}
     */
    public function showEstudiante($id)
    {
        $estudiante = User::where('role', 'estudiante')->findOrFail($id, [
            'id', 'name', 'email', 'phone', 'ci', 'created_at', 'updated_at'
        ]);

        return response()->json($estudiante);
    }

    /**
     * PUT /api/estudiantes/{id}
     */
    public function updateEstudiante(Request $request, $id)
    {
        $estudiante = User::where('role', 'estudiante')->findOrFail($id);

        $data = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'email' => "sometimes|required|email|unique:users,email,{$id}",
            'phone' => 'nullable|string|max:20',
            'ci'    => "sometimes|required|string|unique:users,ci,{$id}|max:20",
        ]);

        $estudiante->update($data);

        return response()->json($estudiante);
    }

    /**
     * DELETE /api/estudiantes/{id}
     */
    public function destroyEstudiante($id)
    {
        $estudiante = User::where('role', 'estudiante')->findOrFail($id);
        $estudiante->delete(); // o->forceDelete() si lo prefieres

        return response()->noContent();
    }
    
}
