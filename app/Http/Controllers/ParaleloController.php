<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paralelo;

class ParaleloController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Paralelo::with('teacher')->get();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'grade' => 'required|string',
            'section' => 'required|string',
            'teacher_id' => 'required|exists:users,id',
        ]);
        return Paralelo::create($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(Paralelo $paralelo)
    {
        return $paralelo->load('teacher');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Paralelo $paralelo)
    {
        $data = $request->validate([
            'grade' => 'sometimes|string',
            'section' => 'sometimes|string',
            'teacher_id' => 'sometimes|exists:users,id',
        ]);
        $paralelo->update($data);
        return $paralelo;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Paralelo $paralelo)
    {
        $paralelo->delete();
        return response()->noContent();
    }
}
