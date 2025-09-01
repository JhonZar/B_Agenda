<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reporte;
use Illuminate\Http\JsonResponse;

class ReporteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $reportes = Reporte::with(['student', 'teacher', 'category'])->get();
        return response()->json($reportes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'teacher_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categorias_reportes,id',
            'description' => 'required|string',
        ]);

        $reporte = Reporte::create($validated);

        return response()->json($reporte->load(['student', 'teacher', 'category']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $reporte = Reporte::with(['student', 'teacher', 'category'])->findOrFail($id);
        return response()->json($reporte);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'teacher_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categorias_reportes,id',
            'description' => 'required|string',
        ]);

        $reporte = Reporte::findOrFail($id);
        $reporte->update($validated);

        return response()->json($reporte->load(['student', 'teacher', 'category']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $reporte = Reporte::findOrFail($id);
        $reporte->delete();

        return response()->json(null, 204);
    }
}
