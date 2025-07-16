<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class MateriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Materia::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|unique:materias,name',
            'description' => 'nullable|string',
        ]);

        $materia = Materia::create($data);

        return response($materia, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Materia $materia)
    {
        return $materia;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Materia $materia)
    {
        $data = $request->validate([
            'name'        => "required|string|unique:materias,name,{$materia->id}",
            'description' => 'nullable|string',
        ]);

        $materia->update($data);

        return response($materia, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Materia $materia)
    {
        $materia->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
