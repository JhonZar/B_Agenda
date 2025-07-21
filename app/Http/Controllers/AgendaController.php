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

        // Inicio con sólo globales
        $query = Agenda::query()->global();

        // Añadir las de grado si se pidió
        if ($grade) {
            $query->orWhere(function ($q) use ($grade) {
                $q->forGrade($grade);
            });
        }

        // Añadir las de paralelo si se pidió
        if ($paraleloId) {
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

        $agenda = Agenda::create($data);

        return response()->json($agenda, Response::HTTP_CREATED);
    }

    /**
     * Mostrar un único evento.
     */
    public function show(Agenda $agenda)
    {
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

        $agenda->update($data);

        return response()->json($agenda, Response::HTTP_OK);
    }

    /**
     * Eliminar un evento de la agenda.
     */
    public function destroy(Agenda $agenda)
    {
        $agenda->delete();

        return response()->noContent();
    }
}
