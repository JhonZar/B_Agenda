<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reporte;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ReporteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Reporte::with(['student', 'teacher', 'category']);
        if ($user && $user->role === 'profesor') {
            $query->where('teacher_id', $user->id);
        }
        $reportes = $query->orderByDesc('id')->get();
        return response()->json($reportes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $rules = [
            'student_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categorias_reportes,id',
            'description' => 'required|string',
        ];
        if ($user && $user->role === 'admin') {
            $rules['teacher_id'] = 'required|exists:users,id';
        }
        $validated = $request->validate($rules);

        if ($user && $user->role === 'profesor') {
            $validated['teacher_id'] = $user->id;
            $owns = \DB::table('paralelo_estudiante as pe')
                ->join('paralelos as p', 'p.id', '=', 'pe.paralelos_id')
                ->where('pe.student_id', $validated['student_id'])
                ->where('p.teacher_id', $user->id)
                ->exists();
            if (!$owns) {
                return response()->json(['message' => 'No autorizado para reportar a este estudiante'], 403);
            }
        }

        $reporte = Reporte::create($validated);
        $reporte->load(['student', 'teacher', 'category']);

        // Notificar a los padres por WhatsApp (no bloquear si falla)
        try {
            $this->notifyParentsWhatsapp($reporte);
        } catch (\Throwable $e) {
            Log::warning('Fallo al notificar reporte via WhatsApp: '.$e->getMessage(), [
                'report_id' => $reporte->id,
            ]);
        }

        return response()->json($reporte, 201);
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
        $reporte = Reporte::findOrFail($id);
        $user = $request->user();

        if ($user && $user->role === 'profesor' && $reporte->teacher_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $rules = [
            'student_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categorias_reportes,id',
            'description' => 'required|string',
        ];
        if ($user && $user->role === 'admin') {
            $rules['teacher_id'] = 'required|exists:users,id';
        }
        $validated = $request->validate($rules);

        if ($user && $user->role === 'profesor') {
            $validated['teacher_id'] = $user->id;
            $owns = \DB::table('paralelo_estudiante as pe')
                ->join('paralelos as p', 'p.id', '=', 'pe.paralelos_id')
                ->where('pe.student_id', $validated['student_id'])
                ->where('p.teacher_id', $user->id)
                ->exists();
            if (!$owns) {
                return response()->json(['message' => 'No autorizado para este estudiante'], 403);
            }
        }

        $reporte->update($validated);
        $reporte->load(['student', 'teacher', 'category']);
        return response()->json($reporte);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $reporte = Reporte::findOrFail($id);
        $user = $request->user();
        if ($user && $user->role === 'profesor' && $reporte->teacher_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $reporte->delete();
        return response()->json(null, 204);
    }
    private function notifyParentsWhatsapp(Reporte $reporte): void
    {
        $student = $reporte->student ?? User::with('padres')->find($reporte->student_id);
        if (!$student) return;

        $parents = method_exists($student, 'padres') ? $student->padres : collect();
        if ($parents->isEmpty()) return;

        $teacherName = $reporte->teacher->name ?? 'Docente';
        $studentName = $student->name ?? 'Estudiante';
        $category   = $reporte->category->name ?? 'General';
        $desc       = $reporte->description;
        $when       = now()->format('d/m/Y H:i');

        $message = "Aviso de Reporte Académico\n\n".
            "Estudiante: *{$studentName}*\n".
            "Categoría: *{$category}*\n".
            "Docente: *{$teacherName}*\n".
            "Fecha: {$when}\n\n".
            "Detalle: {$desc}";

        $server = env('WHATSAPP_SERVER_URL', 'http://localhost:3001');

        foreach ($parents as $padre) {
            $to = $padre->phone;
            if (!$to) continue;
            try {
                $resp = Http::post("{$server}/api/whatsapp/send-message", [
                    'to' => $to,
                    'message' => $message,
                ]);
                if (! $resp->successful()) {
                    Log::warning('WhatsApp no envió reporte', [
                        'parent_id' => $padre->id,
                        'status' => $resp->status(),
                        'body' => $resp->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Excepción enviando WhatsApp de reporte: '.$e->getMessage(), [
                    'parent_id' => $padre->id,
                ]);
            }
        }
    }
}
