<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

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
}
