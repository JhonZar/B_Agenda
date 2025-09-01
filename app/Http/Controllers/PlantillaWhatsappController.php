<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlantillaWhatsapp;
use Illuminate\Http\JsonResponse;

class PlantillaWhatsappController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(PlantillaWhatsapp::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'created_by' => 'required|exists:users,id',
        ]);

        $template = PlantillaWhatsapp::create($validated);

        return response()->json($template, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $template = PlantillaWhatsapp::findOrFail($id);
        return response()->json($template);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'created_by' => 'required|exists:users,id',
        ]);

        $template = PlantillaWhatsapp::findOrFail($id);
        $template->update($validated);

        return response()->json($template);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $template = PlantillaWhatsapp::findOrFail($id);
        $template->delete();

        return response()->json(null, 204);
    }
}
