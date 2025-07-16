<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\ParaleloController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ParaleloMateriaController;
use App\Http\Controllers\ParaleloEstudianteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas públicas (no requieren autenticación)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);


// Rutas protegidas (requieren un token de Sanctum)
// Todas estas rutas requieren autenticación con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Rutas exclusivas para 'admin'
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', function () {
            return response()->json(['message' => 'Bienvenido al panel de administración!']);
        });
        Route::post('/admin/crear-usuario', function () {
            return response()->json(['message' => 'Admin puede crear usuarios.']);
        });

        Route::apiResource('paralelos', ParaleloController::class);
        Route::get('/profesores', [UserController::class, 'profesores']);
        Route::post('/profesores', [UserController::class, 'storeProfesor']);
        Route::apiResource('materias', MateriaController::class);
        Route::apiResource('paralelos.materias', ParaleloMateriaController::class)
            ->only(['index', 'store', 'destroy']);
        Route::put(
            'paralelos/{paralelo}/materias',
            [ParaleloMateriaController::class, 'update']
        );
        Route::apiResource('paralelos.estudiantes', ParaleloEstudianteController::class)
            ->only(['index', 'store', 'update', 'destroy']);
        Route::put('/paralelos/{paralelo}/estudiantes', [ParaleloEstudianteController::class, 'update']);

        Route::get('/estudiantes',            [UserController::class, 'indexEstudiante']);
        Route::post('/estudiantes',            [UserController::class, 'storeEstudiante']);
        Route::get('/estudiantes/{id}',       [UserController::class, 'showEstudiante']);
        Route::put('/estudiantes/{id}',       [UserController::class, 'updateEstudiante']);
        Route::delete('/estudiantes/{id}',       [UserController::class, 'destroyEstudiante']);
    });

    // Rutas para 'profesor' (y también 'admin' si así lo deseas)
    Route::middleware('role:profesor,admin')->group(function () {
        Route::get('/profesor/cursos', function () {
            return response()->json(['message' => 'Lista de cursos para profesores.']);
        });
        Route::post('/profesor/notas', function () {
            return response()->json(['message' => 'Profesor puede subir notas.']);
        });
        // Más rutas para profesores
    });

    // Rutas para 'padre' (y también 'admin')
    Route::middleware('role:padre,admin')->group(function () {
        Route::get('/padre/hijos', function () {
            return response()->json(['message' => 'Información de los hijos para padres.']);
        });
        // Más rutas para padres
    });

    // Rutas para 'estudiante' (y también 'admin', 'profesor', 'padre' si lo necesitas)
    Route::middleware('role:estudiante,admin,profesor,padre')->group(function () {
        Route::get('/estudiante/horario', function () {
            return response()->json(['message' => 'Horario del estudiante.']);
        });
        Route::get('/estudiante/calificaciones', function () {
            return response()->json(['message' => 'Consulta tus calificaciones.']);
        });
        // Más rutas para estudiantes
    });
});
