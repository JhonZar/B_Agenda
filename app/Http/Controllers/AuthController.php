<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\CodigoOtp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validación de los datos de entrada
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:admin,profesor,padre,estudiante',
            'phone' => 'nullable|string|max:20',
        ]);

        // Crear el nuevo usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $request->phone,
        ]);

        // Crear un token de autenticación para el usuario
        $token = $user->createToken('auth_token')->plainTextToken;

        // Responder con el usuario y el token
        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201); // Código de estado 201 para "Created"
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales inválidas.'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
    public function logout(Request $request)
    {
        // Revocar el token actual del usuario
        $request->user()->currentAccessToken()->delete();

        // Responder con un mensaje de éxito
        return response()->json([
            'message' => 'Sesión cerrada exitosamente y token revocado.',
        ]);
    }

    public function user(Request $request)
    {
        // Devolver la información del usuario autenticado
        return response()->json($request->user());
    }
    public function sendOtp(Request $request)
    {
        // 1. Validación inicial del número de teléfono
        $request->validate([
            'phone' => 'required|string|max:20', // Eliminamos 'exists:users,phone' de aquí para manejarlo manualmente y dar un mensaje más específico
        ]);

        $phoneNumber = $request->phone;

        // 2. Buscar al usuario por número de teléfono
        $user = User::where('phone', $phoneNumber)->first();

        // 3. Casos de validación del usuario y rol
        if (!$user) {
            // El número de teléfono no está en el registro (en la tabla users)
            Log::warning("Intento de OTP para número no registrado: {$phoneNumber}");
            return response()->json([
                'message' => 'Este número de teléfono no está registrado en la escuela. Por favor, verifica el número o comunícate con el administrador.',
                'status' => 'not_registered'
            ], 404); // Código 404 Not Found, ya que el recurso (usuario) no fue encontrado para OTP.
        }

        if ($user->role !== 'padre') {
            // El número pertenece a un usuario, pero no es un rol 'padre'
            Log::warning("Intento de OTP para rol no permitido: {$user->email} (Rol: {$user->role})");
            return response()->json([
                'message' => 'Solo los usuarios con rol de padre pueden iniciar sesión con OTP. Si crees que hay un error, comunícate con el administrador.',
                'status' => 'unauthorized_role'
            ], 403); // Código 403 Forbidden, ya que el rol no tiene permiso para esta acción.
        }

        // 4. Generar y guardar el OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        CodigoOtp::updateOrCreate(
            ['padre_id' => $user->id],
            [
                'codigo' => $otp,
                'expira_en' => Carbon::now()->addMinutes(5),
            ]
        );

        // 5. Intentar enviar el OTP a través del servidor de Node.js (WhatsApp)
        try {
            $whatsappServerUrl = env('WHATSAPP_SERVER_URL', 'http://localhost:3001');

            $response = Http::post("{$whatsappServerUrl}/api/whatsapp/send-message", [
                'to' => $phoneNumber,
                'message' => "Tu código de verificación para iniciar sesión es: *{$otp}*. Válido por 5 minutos. No lo compartas con nadie.",
            ]);

            if ($response->successful()) {
                // Envío exitoso
                Log::info("OTP enviado a {$phoneNumber} a través del servidor de WhatsApp.");
                return response()->json([
                    'message' => 'Código OTP enviado exitosamente a tu WhatsApp. Revisa tu chat.',
                    'status' => 'otp_sent'
                ], 200); // Código 200 OK
            } else {
                // Fallo en el envío por parte del servidor de WhatsApp (Node.js)
                $errorMessage = 'Error desconocido al enviar el código OTP.';
                $statusCode = 500; // Por defecto un error interno del servidor

                $responseBody = $response->json(); // Intentar obtener el cuerpo JSON de la respuesta
                if (isset($responseBody['error'])) {
                    $errorMessage = $responseBody['error']; // Usar el mensaje de error de Node.js
                    if ($response->status() === 503) {
                        $statusCode = 503; // Servicio no disponible (WhatsApp no conectado)
                        $errorMessage = 'Nuestro servicio de WhatsApp no está disponible en este momento. Por favor, inténtalo de nuevo en unos minutos.';
                    } else if ($response->status() === 400) {
                        $statusCode = 400; // Mala solicitud (ej. número mal formateado)
                        $errorMessage = 'El formato del número de teléfono es incorrecto para el envío de WhatsApp. Comunícate con soporte.';
                    }
                }

                Log::error("Fallo al enviar OTP a {$phoneNumber} via WhatsApp server (status: {$response->status()}): " . $response->body());
                return response()->json([
                    'message' => "Fallo al enviar el código OTP: {$errorMessage}",
                    'status' => 'whatsapp_send_failed'
                ], $statusCode);
            }
        } catch (\Exception $e) {
            // Excepción al comunicarse con el servidor de Node.js (ej. servidor Node.js caído o inaccesible)
            Log::error("Excepción al comunicarse con el servidor de WhatsApp para {$phoneNumber}: " . $e->getMessage());
            return response()->json([
                'message' => 'No se pudo conectar con nuestro servicio de WhatsApp. Por favor, inténtalo más tarde o comunícate con el administrador.',
                'status' => 'whatsapp_connection_failed'
            ], 500); // Código 500 Internal Server Error
        }
    }
    public function verifyOtp(Request $request)
    {
        // 1. Validación de entrada
        $data = $request->validate([
            'phone' => 'required|string|max:20',
            'otp'   => 'required|string|size:6',
        ]);

        // 2. Buscar al padre por teléfono
        $user = User::where('phone', $data['phone'])->first();
        if (! $user) {
            return response()->json([
                'message' => 'Número no registrado.',
                'status'  => 'not_registered',
            ], 404);
        }

        // 3. Validar rol
        if ($user->role !== 'padre') {
            return response()->json([
                'message' => 'Solo rol padre puede usar OTP.',
                'status'  => 'unauthorized_role',
            ], 403);
        }

        // 4. Buscar el registro de OTP
        $otpRecord = CodigoOtp::where('padre_id', $user->id)
            ->where('codigo', $data['otp'])
            ->first();

        if (! $otpRecord) {
            return response()->json([
                'message' => 'Código OTP inválido.',
                'status'  => 'invalid_otp',
            ], 400);
        }

        // 5. Verificar expiración
        if (Carbon::now()->gt($otpRecord->expira_en)) {
            // OTP expirado: borramos el registro para limpiar
            $otpRecord->delete();

            return response()->json([
                'message' => 'El código OTP ha expirado.',
                'status'  => 'otp_expired',
            ], 400);
        }

        // 6. OTP válido: liberar o borrar registro
        $otpRecord->delete();

        // 7. Generar token de acceso (revocando previos)
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        // 8. Responder con usuario + token
        return response()->json([
            'message'      => 'OTP verificado. Inicio de sesión exitoso.',
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 200);
    }
}
