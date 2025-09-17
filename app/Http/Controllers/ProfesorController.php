<?php

namespace App\Http\Controllers;

use App\Models\Paralelo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfesorController extends Controller
{
    /**
     * Listado de cursos (paralelos) asignados al profesor autenticado.
     */
    public function cursos(Request $request): JsonResponse
    {
        $user = $request->user();

        $paralelos = Paralelo::query()
            ->with(['materias:id,name'])
            ->withCount('students')
            ->where('teacher_id', $user->id)
            ->orderBy('grade')
            ->orderBy('section')
            ->get(['id', 'grade', 'section', 'teacher_id']);

        return response()->json($paralelos);
    }

    /**
     * Detalle de un curso del profesor: materias y estudiantes.
     */
    public function cursoShow(Request $request, Paralelo $paralelo): JsonResponse
    {
        $user = $request->user();

        // Restringe acceso: profesor sólo puede ver sus cursos; admin puede ver todos
        if (!in_array($user->role, ['admin']) && $paralelo->teacher_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $paralelo->load([
            'materias:id,name',
            'students:id,name,email,phone',
        ]);

        return response()->json($paralelo);
    }

    /**
     * Materias de un curso.
     */
    public function cursoMaterias(Request $request, Paralelo $paralelo): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role, ['admin']) && $paralelo->teacher_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $materias = $paralelo->materias()->get(['materias.id', 'materias.name']);
        return response()->json($materias);
    }
}

