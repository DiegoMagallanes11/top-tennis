<?php

use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\CanchaController;
use App\Http\Controllers\CanchaPublicoController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\PagoDemoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValidacionController;
use App\Models\Cancha;
use Illuminate\Support\Facades\Route;

// RUTAS WEB — ORGANIZADAS POR NIVEL DE ACCESO (MIDDLEWARE):
//   publico           -> welcome (landing)
//   auth              -> dashboard, perfil, flujo de reserva del cliente
//   auth + role:admin -> CRUD de canchas, tarifas y horarios,
//                        reserva manual y confirmación de pagos en efectivo
// 'role:' ES EL MIDDLEWARE PERSONALIZADO RoleMiddleware (app/Http/Middleware)

Route::get('/', function () {
    // Muestra las canchas reales del club (operativas) en la landing.
    $canchas = Cancha::where('estado_mantenimiento', 'operativa')
        ->orderBy('nombre')
        ->take(6)
        ->get();

    return view('welcome', compact('canchas'));
})->name('welcome');

// PÚBLICO: página de canchas para clientes (sin autenticación)
Route::get('/club/canchas', [CanchaPublicoController::class, 'index'])->name('canchas.publico');

// VALIDACIÓN PÚBLICA DEL QR: cualquiera que escanee el ticket ve su estado.
// El token es aleatorio y único, así que no se puede adivinar.
Route::get('/validar/{token}', [ValidacionController::class, 'mostrar'])->name('validacion.mostrar');

// DASHBOARD: estadísticas distintas según el rol (ver DashboardController).
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ===== ÁREA DE STAFF (admin + recepcionista) =====
// Calendario, disponibilidad en vivo y control de acceso por QR.
Route::middleware(['auth', 'role:admin,recepcionista'])->group(function () {
    Route::get('/calendario', [CalendarioController::class, 'index'])->name('calendario.index');
    Route::get('/calendario/datos', [CalendarioController::class, 'datos'])->name('calendario.datos');
    Route::post('/validar/{token}/usar', [ValidacionController::class, 'marcarUsada'])->name('validacion.usar');
});

// ===== CRUDs EXCLUSIVOS DEL ADMIN =====
// Solo el administrador: usuarios, canchas, tarifas y horarios.
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/club/reglas', [ClubController::class, 'edit'])->name('club.edit');
    Route::patch('/club/reglas', [ClubController::class, 'update'])->name('club.update');
    Route::get('/club/auditoria', [ClubController::class, 'auditoria'])->name('club.auditoria');
    // Gestión de usuarios (crear, editar, eliminar, activar/desactivar)
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/create', [UserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{usuario}/edit', [UserController::class, 'edit'])->name('usuarios.edit');
    Route::patch('/usuarios/{usuario}', [UserController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{usuario}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    Route::patch('/usuarios/{usuario}/activo', [UserController::class, 'toggleActivo'])->name('usuarios.toggleActivo');

    // Canchas
    Route::get('/canchas', [CanchaController::class, 'index'])->name('canchas.index');
    Route::get('/canchas/create', [CanchaController::class, 'create'])->name('canchas.create');
    Route::post('/canchas', [CanchaController::class, 'store'])->name('canchas.store');
    Route::get('/canchas/{cancha}/edit', [CanchaController::class, 'edit'])->name('canchas.edit');
    Route::patch('/canchas/{cancha}', [CanchaController::class, 'update'])->name('canchas.update');
    Route::delete('/canchas/{cancha}', [CanchaController::class, 'destroy'])->name('canchas.destroy');
    Route::post('/canchas/{cancha}/mantenimiento', [CanchaController::class, 'ponerMantenimiento'])->name('canchas.mantenimiento');
    Route::post('/canchas/{cancha}/restaurar', [CanchaController::class, 'restaurar'])->name('canchas.restaurar');

    // Tarifas
    Route::get('/tarifas', [TarifaController::class, 'index'])->name('tarifas.index');
    Route::get('/tarifas/create', [TarifaController::class, 'create'])->name('tarifas.create');
    Route::post('/tarifas', [TarifaController::class, 'store'])->name('tarifas.store');
    Route::get('/tarifas/{tarifa}/edit', [TarifaController::class, 'edit'])->name('tarifas.edit');
    Route::patch('/tarifas/{tarifa}', [TarifaController::class, 'update'])->name('tarifas.update');
    Route::delete('/tarifas/{tarifa}', [TarifaController::class, 'destroy'])->name('tarifas.destroy');

    // Horarios (slots)
    Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');
    Route::get('/horarios/create', [HorarioController::class, 'create'])->name('horarios.create');
    Route::post('/horarios', [HorarioController::class, 'store'])->name('horarios.store');
    Route::get('/horarios/{horario}/edit', [HorarioController::class, 'edit'])->name('horarios.edit');
    Route::patch('/horarios/{horario}', [HorarioController::class, 'update'])->name('horarios.update');
    Route::delete('/horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy');
    Route::delete('/horarios-dia', [HorarioController::class, 'eliminarDia'])->name('horarios.eliminarDia');
    Route::post('/horarios/cambiar-tarifa', [HorarioController::class, 'cambiarTarifaMasiva'])->name('horarios.cambiarTarifa');
});

// ===== RESERVAS: ACCESIBLES POR TODOS LOS USUARIOS AUTENTICADOS =====
// LAS RUTAS DE RESERVA MANUAL TIENEN MIDDLEWARE ADICIONAL role:admin,recepcionista
// LAS RUTAS ESTATICAS (/crear-manual) VAN ANTES DE LAS PARAMETRIZADAS (/{reserva}) PARA EVITAR CONFLICTO
Route::middleware('auth')->group(function () {
    Route::get('/reservar', [ReservaController::class, 'disponibles'])->name('reservas.disponibles');
    Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas.index');
    Route::get('/reservar/{horario}/confirmar', [ReservaController::class, 'confirmar'])->name('reservas.confirmar');
    Route::post('/reservas', [ReservaController::class, 'store'])
        ->middleware('throttle:10,1')->name('reservas.store');

    // Reserva manual (staff: admin o recepcionista) — DEBE ir antes de {reserva} routes
    Route::get('/reservas/crear-manual', [ReservaController::class, 'crearManual'])
        ->middleware('role:admin,recepcionista')->name('reservas.crearManual');
    Route::post('/reservas/manual', [ReservaController::class, 'storeManual'])
        ->middleware(['role:admin,recepcionista', 'throttle:10,1'])->name('reservas.storeManual');

    Route::get('/reservas/{reserva}/ticket', [ReservaController::class, 'ticket'])->withTrashed()->name('reservas.ticket')->middleware('ownReservation');
    Route::get('/reservas/{reserva}/pago-demo', [PagoDemoController::class, 'mostrar'])->name('reservas.pagoDemo')->middleware('ownReservation');
    Route::post('/reservas/{reserva}/pago-demo', [PagoDemoController::class, 'simular'])->middleware(['throttle:5,1', 'ownReservation'])->name('reservas.simularPago');
    Route::get('/reservas/{reserva}/edit', [ReservaController::class, 'edit'])->name('reservas.edit')->middleware('ownReservation');
    Route::patch('/reservas/{reserva}', [ReservaController::class, 'update'])->name('reservas.update')->middleware('ownReservation');
    Route::patch('/reservas/{reserva}/estado', [ReservaController::class, 'gestionar'])->middleware(['role:admin,recepcionista', 'ownReservation'])->name('reservas.gestionar');
    Route::get('/reservas/{reserva}/ticket/pdf', [ReservaController::class, 'descargarTicket'])->withTrashed()->name('reservas.ticket.pdf')->middleware('ownReservation');
    Route::delete('/reservas/{reserva}', [ReservaController::class, 'cancelar'])->name('reservas.cancelar')->middleware('ownReservation');
    Route::patch('/reservas/{reserva}/confirmar-pago', [ReservaController::class, 'confirmarPago'])
        ->middleware(['role:admin,recepcionista', 'ownReservation'])->name('reservas.confirmarPago');
});

// RUTAS DE AUTENTICACION GENERADAS POR LARAVEL BREEZE (LOGIN, REGISTRO, PASSWORD RESET, ETC.)
require __DIR__.'/auth.php';
