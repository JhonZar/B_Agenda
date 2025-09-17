<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Paralelo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    /**
     * GET /attendance/history
     */
    public function index()
    {
        // Traemos fecha+paralelo y además creator y paralelos en un sólo query
        $sessions = Asistencia::with(['creator', 'paralelo'])
            ->select('fecha', 'paralelo_id')
            ->groupBy('fecha', 'paralelo_id')
            ->orderByDesc('fecha')
            ->get()
            ->map(function ($item) {
                // Todas las filas de ese grupo:
                $asistencias = Asistencia::with('creator')
                    ->where('fecha', $item->fecha)
                    ->where('paralelo_id', $item->paralelo_id)
                    ->get();

                $first = $asistencias->first();
                $counts = $asistencias->pluck('estado')->countBy();

                // Construimos el nombre legible del paralelo
                $paralelo = $item->paralelo;
                $parallelName = "{$paralelo->grade}-{$paralelo->section}";

                return [
                    'date'          => $item->fecha->toDateString(),
                    'parallel'      => $parallelName,
                    'totalStudents' => $paralelo->students()->count(),
                    'presentCount'  => $counts['present'] ?? 0,
                    'absentCount'   => $counts['absent']  ?? 0,
                    'lateCount'     => $counts['late']    ?? 0,
                    'excusedCount'  => $counts['excused'] ?? 0,
                    'takenBy'       => $first?->creator?->name,
                    'takenAt'       => $first?->created_at->format('H:i'),
                ];
            });

        return response()->json($sessions);
    }

    /**
     * POST /attendance
     */
    public function store(Request $request)
    {
        $payload = $request->validate([
            'date'        => 'required|date',
            'paralelo_id' => 'required|exists:paralelos,id',
            'students'    => 'required|array|min:1',
            'students.*.id'          => 'required|exists:users,id',
            'students.*.status'      => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
            'students.*.arrivalTime' => 'nullable|date_format:H:i',
            'students.*.notes'       => 'nullable|string',
        ]);

        // Authorization: profesor solo puede registrar en sus propios paralelos
        $paralelo = Paralelo::findOrFail($payload['paralelo_id']);
        $user = $request->user();
        if ($user->role !== 'admin' && $paralelo->teacher_id !== $user->id) {
            return response()->json(['message' => 'No autorizado para este curso'], 403);
        }

        foreach ($payload['students'] as $stu) {
            Asistencia::updateOrCreate(
                [
                    'estudiante_id' => $stu['id'],
                    'paralelo_id'   => $payload['paralelo_id'],
                    'fecha'         => $payload['date'],
                ],
                [
                    'estado'       => $stu['status'],
                    'hora_llegada' => $stu['arrivalTime'] ?? null,
                    'notas'        => $stu['notes'] ?? null,
                    'created_by'   => $request->user()->id,
                ]
            );
        }

        return response()->json(['message' => 'Asistencia guardada'], 201);
    }

    /**
     * GET /attendance?date=YYYY-MM-DD&paralelo_id=X
     */
    public function show(Request $request)
    {
        $data = $request->validate([
            'date'        => 'required|date',
            'paralelo_id' => 'required|exists:paralelos,id',
        ]);

        // Authorization: profesor solo puede ver sus propios paralelos
        $paralelo = Paralelo::findOrFail($data['paralelo_id']);
        $user = $request->user();
        if ($user->role !== 'admin' && $paralelo->teacher_id !== $user->id) {
            return response()->json(['message' => 'No autorizado para este curso'], 403);
        }

        $asistencias = Asistencia::with(['estudiante', 'creator'])
            ->where('fecha', $data['date'])
            ->where('paralelo_id', $data['paralelo_id'])
            ->get();

        $parallelName = "{$paralelo->grade}-{$paralelo->section}";
        $first      = $asistencias->first();
        $counts     = $asistencias->pluck('estado')->countBy();

        return response()->json([
            'id'            => "{$data['paralelo_id']}_{$data['date']}",
            'date'          => $data['date'],
            'parallel'      => $parallelName,
            'totalStudents' => $paralelo->students()->count(),
            'presentCount'  => $counts['present'] ?? 0,
            'absentCount'   => $counts['absent']  ?? 0,
            'lateCount'     => $counts['late']    ?? 0,
            'excusedCount'  => $counts['excused'] ?? 0,
            'takenBy'       => $first?->creator?->name,
            'takenAt'       => optional($first)->created_at?->format('H:i'),
            'students'      => $asistencias->map(fn($a) => [
                'id'          => $a->estudiante->id,
                'name'        => $a->estudiante->name,
                'parallel'    => $parallelName,
                'status'      => $a->estado,
                'arrivalTime' => $a->hora_llegada,
                'notes'       => $a->notas,
            ]),
        ]);
    }
}
