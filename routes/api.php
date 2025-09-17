<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\ParaleloController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ParaleloMateriaController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ParaleloEstudianteController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\ReportCategoryController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\PadreEstudianteController; 
use App\Http\Controllers\PlantillaWhatsappController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RfidIngestController;
use App\Http\Controllers\ProfesorController;

// Rutas públicas (no requieren autenticación)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/attendance/ingest', [RfidIngestController::class, 'ingest']);


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
        Route::post('/estudiantes',           [UserController::class, 'storeEstudiante']);
        Route::get('/estudiantes/{id}',       [UserController::class, 'showEstudiante']);
        Route::put('/estudiantes/{id}',       [UserController::class, 'updateEstudiante']);
        Route::delete('/estudiantes/{id}',    [UserController::class, 'destroyEstudiante']);

        // Rutas para padres
        Route::get('/padres',            [UserController::class, 'indexPadre']);
        Route::post('/padres',           [UserController::class, 'storePadre']);
        Route::put('/padres/{id}',       [UserController::class, 'updatePadre']);
        Route::delete('/padres/{id}',    [UserController::class, 'destroyPadre']);

        // Vinculaciones padre-estudiante
        Route::get('/padres-estudiantes',    [PadreEstudianteController::class, 'index']);
        Route::post('/padres-estudiantes',   [PadreEstudianteController::class, 'store']);
        Route::get('/padres-estudiantes/{id}', [PadreEstudianteController::class, 'show']);
        Route::put('/padres-estudiantes/{id}',  [PadreEstudianteController::class, 'update']);
        Route::delete('/padres-estudiantes/{id}', [PadreEstudianteController::class, 'destroy']);

        Route::get('/attendance/history', [AttendanceController::class, 'index']);

        Route::apiResource('agendas', AgendaController::class);

        Route::apiResource('report-categories', ReportCategoryController::class);

        // WhatsApp Templates
        Route::apiResource('whatsapp-templates', PlantillaWhatsappController::class);
    
        Route::post('/rfid/assign', [RfidIngestController::class, 'assign']);
    });

    // Rutas para 'profesor' (y también 'admin' si así lo deseas)
    Route::middleware('role:profesor,admin')->group(function () {
        // Cursos del profesor
        Route::get('/profesor/cursos', [ProfesorController::class, 'cursos']);
        Route::get('/profesor/cursos/{paralelo}', [ProfesorController::class, 'cursoShow']);
        Route::get('/profesor/cursos/{paralelo}/materias', [ProfesorController::class, 'cursoMaterias']);

        // Asistencias: profesores y admins pueden crear/consultar
        Route::get('/attendance',  [AttendanceController::class, 'show']);
        Route::post('/attendance', [AttendanceController::class, 'store']);

        // Agenda: profesor y admin
        Route::get('/agendas', [AgendaController::class, 'index']);
        Route::get('/agendas/{agenda}', [AgendaController::class, 'show']);
        Route::post('/agendas', [AgendaController::class, 'store']);
        Route::put('/agendas/{agenda}', [AgendaController::class, 'update']);
        Route::delete('/agendas/{agenda}', [AgendaController::class, 'destroy']);

        // Reportes
        Route::apiResource('reportes', ReporteController::class);

        // Categorías de reportes: lectura para profesor (gestión completa ya en admin)
        Route::get('/report-categories', [ReportCategoryController::class, 'index']);
        Route::get('/report-categories/{id}', [ReportCategoryController::class, 'show']);
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
