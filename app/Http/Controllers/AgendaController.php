<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Agenda;
use Illuminate\Http\Response;



class AgendaController extends Controller
{
    public function index(Request $request)
    {
        $grade      = $request->query('grade');
        $paraleloId = $request->query('paralelo_id');
        $user       = $request->user();

        // Inicio con sólo globales
        $query = Agenda::query()->global();

        // Añadir las de grado si se pidió
        if ($grade) {
            // Si es profesor, sólo permitir grados donde tenga paralelos
            if ($user && $user->role === 'profesor') {
                $hasGrade = \App\Models\Paralelo::where('teacher_id', $user->id)
                    ->where('grade', $grade)
                    ->exists();
                if (!$hasGrade) {
                    return response()->json(['message' => 'No autorizado para este grado'], 403);
                }
            }
            $query->orWhere(function ($q) use ($grade) {
                $q->forGrade($grade);
            });
        }

        // Añadir las de paralelo si se pidió
        if ($paraleloId) {
            // Si es profesor, restringe a sus propios paralelos
            if ($user && $user->role === 'profesor') {
                $owns = \App\Models\Paralelo::where('id', (int) $paraleloId)
                    ->where('teacher_id', $user->id)
                    ->exists();
                if (!$owns) {
                    return response()->json(['message' => 'No autorizado para este curso'], 403);
                }
            }
            $query->orWhere(function ($q) use ($paraleloId) {
                $q->forParalelo((int) $paraleloId);
            });
        }

        $events = $query
            ->orderBy('scheduled_at')
            ->get();

        return response()->json($events, Response::HTTP_OK);
    }

    /**
     * Crear un nuevo evento en la agenda.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'paralelo_id'  => 'nullable|exists:paralelos,id',
            'grade'        => 'nullable|string',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'scheduled_at' => 'required|date',
        ]);

        $user = $request->user();
        if ($user && $user->role === 'profesor') {
            // Profesores: sólo pueden crear para sus paralelos; no global ni por grado
            if (empty($data['paralelo_id'])) {
                return response()->json(['message' => 'Debe seleccionar un paralelo propio'], 422);
            }
            $owns = \App\Models\Paralelo::where('id', (int) $data['paralelo_id'])
                ->where('teacher_id', $user->id)
                ->exists();
            if (!$owns) {
                return response()->json(['message' => 'No autorizado para este curso'], 403);
            }
            // Ignorar grado si se envía por error
            $data['grade'] = null;
        }

        $agenda = Agenda::create($data);

        return response()->json($agenda, Response::HTTP_CREATED);
    }

    /**
     * Mostrar un único evento.
     */
    public function show(Request $request, Agenda $agenda)
    {
        $user = $request->user();

        if ($user && $user->role === 'profesor') {
            // Permitir globales
            $isGlobal = is_null($agenda->paralelo_id) && is_null($agenda->grade);
            if ($isGlobal) {
                return response()->json($agenda, Response::HTTP_OK);
            }

            // Permitir por paralelo si pertenece al profesor
            if (!is_null($agenda->paralelo_id)) {
                $owns = \App\Models\Paralelo::where('id', $agenda->paralelo_id)
                    ->where('teacher_id', $user->id)
                    ->exists();
                if (!$owns) {
                    return response()->json(['message' => 'No autorizado'], 403);
                }
                return response()->json($agenda, Response::HTTP_OK);
            }

            // Permitir por grado si el profesor imparte ese grado
            if (!is_null($agenda->grade)) {
                $hasGrade = \App\Models\Paralelo::where('teacher_id', $user->id)
                    ->where('grade', $agenda->grade)
                    ->exists();
                if (!$hasGrade) {
                    return response()->json(['message' => 'No autorizado'], 403);
                }
                return response()->json($agenda, Response::HTTP_OK);
            }
        }

        return response()->json($agenda, Response::HTTP_OK);
    }

    /**
     * Actualizar un evento existente.
     */
    public function update(Request $request, Agenda $agenda)
    {
        $data = $request->validate([
            'paralelo_id'  => 'nullable|exists:paralelos,id',
            'grade'        => 'nullable|string',
            'title'        => 'sometimes|required|string|max:255',
            'description'  => 'nullable|string',
            'scheduled_at' => 'sometimes|required|date',
        ]);

        $user = $request->user();
        if ($user && $user->role === 'profesor') {
            // Profesores: sólo pueden actualizar eventos de sus paralelos; no global ni por grado
            if (is_null($agenda->paralelo_id) && (is_null($agenda->grade) || !is_null($agenda->grade))) {
                return response()->json(['message' => 'No autorizado'], 403);
            }
            $currentOwns = \App\Models\Paralelo::where('id', (int) $agenda->paralelo_id)
                ->where('teacher_id', $user->id)
                ->exists();
            if (!$currentOwns) {
                return response()->json(['message' => 'No autorizado'], 403);
            }
            if (array_key_exists('paralelo_id', $data) && $data['paralelo_id']) {
                $newOwns = \App\Models\Paralelo::where('id', (int) $data['paralelo_id'])
                    ->where('teacher_id', $user->id)
                    ->exists();
                if (!$newOwns) {
                    return response()->json(['message' => 'No autorizado para el nuevo curso'], 403);
                }
            }
            // No permitir establecer grade como profesor
            $data['grade'] = null;
        }

        $agenda->update($data);

        return response()->json($agenda, Response::HTTP_OK);
    }

    /**
     * Eliminar un evento de la agenda.
     */
    public function destroy(Request $request, Agenda $agenda)
    {
        $user = $request->user();
        if ($user && $user->role === 'profesor') {
            // Profesores: sólo pueden eliminar eventos de sus paralelos
            if (is_null($agenda->paralelo_id)) {
                return response()->json(['message' => 'No autorizado'], 403);
            }
            $owns = \App\Models\Paralelo::where('id', (int) $agenda->paralelo_id)
                ->where('teacher_id', $user->id)
                ->exists();
            if (!$owns) {
                return response()->json(['message' => 'No autorizado'], 403);
            }
        }

        $agenda->delete();

        return response()->noContent();
    }
}
