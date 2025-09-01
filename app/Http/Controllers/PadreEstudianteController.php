<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PadreEstudiante;
use App\Models\User;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;

class PadreEstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'padre_id' => 'sometimes|integer|exists:users,id',
            'estudiante_id' => 'sometimes|integer|exists:users,id',
        ]);

        $q = PadreEstudiante::query()
            ->with([
                'padre:id,name,email,phone,ci',
                'estudiante:id,name,email,phone,ci',
            ]);

        if ($request->filled('padre_id')) {
            $q->where('padre_id', $request->integer('padre_id'));
        }

        if ($request->filled('estudiante_id')) {
            $q->where('estudiante_id', $request->integer('estudiante_id'));
        }

        return $q->orderByDesc('id')->get([
            'id',
            'padre_id',
            'estudiante_id',
            'created_at',
            'updated_at'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'padre_id' => 'required|integer|exists:users,id',
            'estudiante_id' => 'required|integer|exists:users,id',
        ]);

        $padre = User::findOrFail($data['padre_id']);
        $estudiante = User::findOrFail($data['estudiante_id']);

        if ($padre->role !== 'padre') {
            return response()->json([
                'message' => "El usuario {$padre->id} no tiene rol padre."
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($estudiante->role !== 'estudiante') {
            return response()->json([
                'message' => "El usuario {$estudiante->id} no tiene rol estudiante."
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $row = PadreEstudiante::firstOrCreate($data);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'La relación padre-estudiante ya existe.'
            ], Response::HTTP_CONFLICT);
        }

        return response()->json($row->load([
            'padre:id,name,email,phone,ci',
            'estudiante:id,name,email,phone,ci',
        ]), Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $row = PadreEstudiante::with([
            'padre:id,name,email,phone,ci',
            'estudiante:id,name,email,phone,ci',
        ])->findOrFail($id);

        return response()->json($row);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $row = PadreEstudiante::findOrFail($id);

        $data = $request->validate([
            'padre_id' => 'sometimes|integer|exists:users,id',
            'estudiante_id' => 'sometimes|integer|exists:users,id',
        ]);

        $row->update($data);

        return response()->json($row->load([
            'padre:id,name,email,phone,ci',
            'estudiante:id,name,email,phone,ci',
        ]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $row = PadreEstudiante::findOrFail($id);
        $row->delete();

        return response()->json(['deleted' => true], Response::HTTP_NO_CONTENT);
    }
}
