<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paralelo;
use App\Models\Materia;
use Illuminate\Http\Response;


class ParaleloMateriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Paralelo $paralelo)
    {
        return $paralelo->materias()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Paralelo $paralelo)
    {
        $data = $request->validate([
            'materia_id' => 'required|exists:materias,id',
        ]);

        // Evita duplicados
        $paralelo->materias()->syncWithoutDetaching($data['materia_id']);

        return response(
            $paralelo->materias()->get(),
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
            'materia_ids'   => 'required|array',
            'materia_ids.*' => 'distinct|exists:materias,id',
        ]);

        // Sync: quita las no incluidas y añade las nuevas
        $paralelo->materias()->sync($data['materia_ids']);

        // Devuelve la lista actualizada
        return response(
            $paralelo->materias()->get(),
            Response::HTTP_OK
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Paralelo $paralelo, Materia $materia)
    {
        $paralelo->materias()->detach($materia->id);

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
