<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paralelo;
use App\Models\User;
use Illuminate\Http\Response;


class ParaleloEstudianteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Paralelo $paralelo)
    {
        return $paralelo->students()->get();
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Paralelo $paralelo)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        // Evita duplicados
        $paralelo->students()->syncWithoutDetaching($data['student_id']);

        return response(
            $paralelo->students()->get(),
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Paralelo $paralelo)
    {
        $data = $request->validate([
            'student_ids'   => 'required|array',
            'student_ids.*' => 'distinct|exists:users,id',
        ]);

        // Reemplaza la lista completa de estudiantes
        $paralelo->students()->sync($data['student_ids']);

        return response(
            $paralelo->students()->get(),
            Response::HTTP_OK
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Paralelo $paralelo, User $estudiante)
    {
        $paralelo->students()->detach($estudiante->id);

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
