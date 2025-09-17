<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PlantillaWhatsapp;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PlantillaWhatsappController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $templates = PlantillaWhatsapp::orderByDesc('created_at')->get();
        return response()->json($templates);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255', 'unique:plantillas_whatsapp,name'],
            'subject' => ['required','string','max:255'],
            'message' => ['required','string'],
            'category' => ['nullable', Rule::in(['academic','administrative','emergency','event','reminder','general'])],
            'status' => ['nullable', Rule::in(['active','inactive','draft'])],
            'target_audience' => ['nullable', Rule::in(['parents','teachers','students','all'])],
            'variables' => ['nullable','array'],
            'variables.*' => ['string'],
            'priority' => ['nullable', Rule::in(['low','medium','high'])],
            'has_attachment' => ['nullable','boolean'],
            'is_schedulable' => ['nullable','boolean'],
            'usage_count' => ['nullable','integer','min:0'],
            'last_used' => ['nullable','date'],
            'created_by' => ['required','exists:users,id'],
        ]);

        // Defaults
        $data = array_merge([
            'category' => 'general',
            'status' => 'draft',
            'target_audience' => 'all',
            'priority' => 'medium',
            'has_attachment' => false,
            'is_schedulable' => false,
            'usage_count' => 0,
        ], $validated);

        // Extract variables if not provided
        if (empty($data['variables'])) {
            $data['variables'] = $this->extractVariables($data['message']);
        }

        // Backward-compat: keep `content` in sync for legacy schema
        $data['content'] = $data['message'] ?? '';

        $template = PlantillaWhatsapp::create($data);

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
        $template = PlantillaWhatsapp::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes','string','max:255', Rule::unique('plantillas_whatsapp','name')->ignore($template->id)],
            'subject' => ['sometimes','string','max:255'],
            'message' => ['sometimes','string'],
            'category' => ['sometimes', Rule::in(['academic','administrative','emergency','event','reminder','general'])],
            'status' => ['sometimes', Rule::in(['active','inactive','draft'])],
            'target_audience' => ['sometimes', Rule::in(['parents','teachers','students','all'])],
            'variables' => ['sometimes','array'],
            'variables.*' => ['string'],
            'priority' => ['sometimes', Rule::in(['low','medium','high'])],
            'has_attachment' => ['sometimes','boolean'],
            'is_schedulable' => ['sometimes','boolean'],
            'usage_count' => ['sometimes','integer','min:0'],
            'last_used' => ['sometimes','date','nullable'],
            'created_by' => ['sometimes','exists:users,id'],
        ]);

        $data = $validated;
        if (!array_key_exists('variables', $data) && array_key_exists('message', $data)) {
            $data['variables'] = $this->extractVariables($data['message']);
        }

        if (array_key_exists('message', $data)) {
            $data['content'] = $data['message'];
        }

        $template->update($data);

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

    /**
     * Extract variables from a message like {name} or {{name}}
     */
    private function extractVariables(string $message): array
    {
        $vars = [];
        // Match {var} or {{var}}
        if (preg_match_all('/\{{1,2}\s*([a-zA-Z0-9_\-]+)\s*\}{1,2}/', $message, $matches)) {
            $vars = array_values(array_unique($matches[1]));
        }
        return $vars;
    }
}
