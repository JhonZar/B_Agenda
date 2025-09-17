<?php

namespace App\Http\Controllers;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\AttendanceRule;

use App\Models\User;
use App\Models\Asistencia;
class RfidIngestController extends Controller
{
    public function ingest(Request $request)
    {
        // Solo recibimos UID (RFID). El resto se infiere en servidor.
        $data = $request->validate([
            'uid'     => 'required|string|max:64',
            'seen_at' => 'nullable|date',
        ]);

        // Hora local (Bolivia) para definir el "día escolar"
        $seenAt = isset($data['seen_at'])
            ? Carbon::parse($data['seen_at'])->setTimezone('America/La_Paz')
            : now('America/La_Paz');

        $date = $seenAt->toDateString();

        // Normaliza UID a mayúsculas (por si se guardó en upper-case)
        $uid = strtoupper($data['uid']);

        // Buscar estudiante por RFID en users.rfid
        $user = User::where('rfid', $uid)->first();

        if (!$user) {
            // UID desconocido: el front que está en modo edición/creación lo usará para autocompletar
            return response()->json([
                'status' => 'unknown',
                'uid'    => $uid,
                'date'   => $date,
            ]);
        }

        // Intentar inferir el paralelo sin recibirlo del cliente:
        // 1) Si el usuario tiene columna paralelo_id, úsala.
        // 2) Si el modelo define relación paralelos(), tomar el primero.
        $paraleloId = null;

        if (isset($user->paralelo_id) && !empty($user->paralelo_id)) {
            $paraleloId = (int) $user->paralelo_id;
        } elseif (method_exists($user, 'paralelos')) {
            try {
                $firstParalelo = $user->paralelos()->select('paralelos.id')->first();
                if ($firstParalelo) {
                    $paraleloId = (int) $firstParalelo->id;
                }
            } catch (\Throwable $e) {
                // Si no existe la relación o falla, seguimos sin paralelo
            }
        }

        if (!$paraleloId) {
            return response()->json([
                'status'  => 'known_no_parallel',
                'message' => 'No se pudo inferir el paralelo del estudiante',
                'uid'     => $uid,
                'user'    => ['id' => $user->id, 'name' => $user->name],
                'date'    => $date,
            ], 422);
        }

        // Regla global de asistencia (ignora paralelo_id: toma la primera activa con paralelo_id NULL)
        $rule = AttendanceRule::where('activo', true)
            ->whereNull('paralelo_id')
            ->orderByDesc('id')
            ->first();

        // Horarios por regla global (defaults si no hay regla)
        $entrada    = $rule?->entrada ?? '08:00:00';
        $tolerancia = (int) ($rule?->tolerancia_min ?? 0);

        // Determinar presente/tarde según hora de llegada vs límite (entrada + tolerancia)
        $horaLlegada = $seenAt->format('H:i');
        $limite = Carbon::createFromFormat('H:i:s', $entrada, 'America/La_Paz')
            ->addMinutes($tolerancia)
            ->format('H:i');
        $estadoSegunRegla = ($horaLlegada <= $limite) ? 'presente' : 'tarde';

        // Múltiples filas por día/estudiante/paralelo.
        // Regla: 1er registro del día = ENTRADA, 2do registro del día = SALIDA.
        $existingCount = Asistencia::where([
            'estudiante_id' => $user->id,
            'paralelo_id'   => $paraleloId,
            'fecha'         => $date,
        ])->count();

        if ($existingCount === 0) {
            // Primera lectura del día -> ENTRADA
            $asistencia = new Asistencia();
            $asistencia->estudiante_id = $user->id;
            $asistencia->paralelo_id   = $paraleloId;
            $asistencia->fecha         = $date;
            $asistencia->estado        = $estadoSegunRegla;
            $asistencia->hora_llegada  = $horaLlegada;
            $asistencia->created_by    = optional($request->user())->id;
            $asistencia->save();
            $action = 'check_in';

            // Notificar a los padres SOLO en el primer check-in del día
            try {
                $whatsappServerUrl = env('WHATSAPP_SERVER_URL', 'http://localhost:3001');
                // Si la relación padres() existe, intentamos enviar a cada padre con teléfono
                if (method_exists($user, 'padres')) {
                    $padres = $user->padres()->select('users.id','users.name','users.phone')->get();
                    foreach ($padres as $p) {
                        $to = $p->phone;
                        if (!$to) {
                            continue;
                        }
                        // Formato rápido: si no empieza con '+', asumimos Bolivia +591
                        $to = preg_replace('/\\s+/', '', $to);
                        if (strpos($to, '+') !== 0) {
                            // si es un número de 8 dígitos, prepende +591
                            if (preg_match('/^\\d{8}$/', $to)) {
                                $to = '+591' . $to;
                            } else {
                                // si ya trae indicativo sin '+', lo dejamos tal cual
                                $to = '+' . ltrim($to, '+');
                            }
                        }
                        $msg = sprintf(
                            'Hola %s, %s registró su ENTRADA a las %s.',
                            $p->name ?? 'madre/padre',
                            $user->name,
                            $horaLlegada
                        );
                        // Enviar y no bloquear si falla
                        try {
                            $resp = Http::timeout(5)->post("{$whatsappServerUrl}/api/whatsapp/send-message", [
                                'to' => $to,
                                'message' => $msg,
                            ]);
                            if ($resp->successful()) {
                                Log::info("WhatsApp aviso entrada OK para padre {$p->id} ({$to}) del estudiante {$user->id}");
                            } else {
                                Log::warning("WhatsApp aviso entrada FALLÓ para padre {$p->id} ({$to}) del estudiante {$user->id}", [
                                    'status' => $resp->status(),
                                    'body' => $resp->json(),
                                ]);
                            }
                        } catch (\Throwable $e) {
                            Log::error('Excepción enviando WhatsApp (entrada): '.$e->getMessage());
                        }
                    }
                }
            } catch (\Throwable $e) {
                // No interrumpir el flujo principal por un fallo de notificación
                Log::warning('Aviso WhatsApp omitido: '.$e->getMessage());
            }
        } elseif ($existingCount === 1) {
            // Segunda lectura del día -> SALIDA (se registra como un segundo row)
            $asistencia = new Asistencia();
            $asistencia->estudiante_id = $user->id;
            $asistencia->paralelo_id   = $paraleloId;
            $asistencia->fecha         = $date;
            // Para salida no recalculamos estado; conservamos el de la regla para trazabilidad
            $asistencia->estado        = $estadoSegunRegla;
            $asistencia->hora_llegada  = $horaLlegada; // reutilizamos campo hora_llegada como "momento de evento"
            $asistencia->created_by    = optional($request->user())->id;
            $asistencia->save();
            $action = 'check_out';

        } else {
            // Tercera o posteriores lecturas del día: actualiza el último si la hora es mayor, si no ignora
            $last = Asistencia::where([
                    'estudiante_id' => $user->id,
                    'paralelo_id'   => $paraleloId,
                    'fecha'         => $date,
                ])->orderByDesc('id')->first();

            if ($last && $horaLlegada > ($last->hora_llegada ?? '00:00')) {
                $last->hora_llegada = $horaLlegada;
                $last->save();
                $asistencia = $last;
                $action = 'check_out'; // lo tratamos como actualización de salida
            } else {
                $asistencia = $last;
                $action = 'ignored_extra_read';
            }
        }

        return response()->json([
            'status' => 'known',
            'uid'    => $uid,
            'user'   => ['id' => $user->id, 'name' => $user->name],
            'attendance' => [
                'date'        => $date,
                'paralelo_id' => $paraleloId,
                'action'      => $action,
                'record_id'   => $asistencia->id,
            ],
        ]);
    }

    /**
     * POST /api/rfid/assign
     * Body: { uid: string, user_id: int }
     * Asigna un UID (users.rfid) a un estudiante existente.
     */
    public function assign(Request $request)
    {
        $data = $request->validate([
            'uid'     => 'required|string|max:64',
            'user_id' => 'required|exists:users,id',
        ]);

        // Asegura unicidad por la constraint UNIQUE en users.rfid
        // Si el UID ya está usado por otro usuario, lanzará excepción al guardar.
        $user = User::findOrFail($data['user_id']);
        $user->rfid = $data['uid'];

        try {
            $user->save();
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'uid' => ['Este UID ya está asignado a otro usuario.'],
            ]);
        }

        return response()->json([
            'ok'   => true,
            'user' => ['id' => $user->id, 'name' => $user->name, 'rfid' => $user->rfid],
        ], 201);
    }
}
