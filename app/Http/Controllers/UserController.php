<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

// GESTIÓN DE USUARIOS (solo admin). Ruta protegida por middleware role:admin.
// Crea/edita/elimina (borrado lógico) y activa/desactiva cuentas.
// Protecciones anti-bloqueo: el admin no puede eliminarse, desactivarse ni quitarse
// a sí mismo el rol de administrador (evita quedarse sin acceso).
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::withCount('reservas')->orderBy('name');

        if ($buscar = trim((string) $request->buscar)) {
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        if (in_array($request->rol, ['admin', 'recepcionista', 'cliente'], true)) {
            $query->where('rol', $request->rol);
        }

        $usuarios = $query->paginate(15)->withQueryString();

        return view('usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('usuarios.create', ['usuario' => new User()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $user = new User([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'telefono' => $data['telefono'] ?? null,
                'password' => Hash::make($data['password']),
            ]);
            // Campos sensibles: asignación explícita (no mass assignment).
            $user->rol    = Rol::from($data['rol']);
            $user->activo = $request->boolean('activo', true);
            $user->save();

            return redirect()->route('usuarios.index')
                ->with('success', "Usuario \"{$user->name}\" creado correctamente.");
        } catch (\Throwable $e) {
            Log::error('Error al crear usuario', ['error' => $e->getMessage()]);
            return back()->withInput()->withErrors(['general' => 'No se pudo crear el usuario. Intenta de nuevo.']);
        }
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', compact('usuario'));
    }

    public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
    {
        try {
            $data   = $request->validated();
            $esSelf = $usuario->id === auth()->id();

            // Anti-bloqueo: no permitir que el admin se quite su propio rol de admin.
            if ($esSelf && $data['rol'] !== Rol::Admin->value) {
                return back()->withInput()
                    ->withErrors(['rol' => 'No puedes quitarte a ti mismo el rol de administrador.']);
            }

            $usuario->fill([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'telefono' => $data['telefono'] ?? null,
            ]);

            if (! empty($data['password'])) {
                $usuario->password = Hash::make($data['password']);
            }

            $usuario->rol = Rol::from($data['rol']);

            // No puede desactivarse a sí mismo desde aquí.
            if (! $esSelf) {
                $usuario->activo = $request->boolean('activo', $usuario->activo);
            }

            $usuario->save();

            return redirect()->route('usuarios.index')
                ->with('success', "Usuario \"{$usuario->name}\" actualizado correctamente.");
        } catch (\Throwable $e) {
            Log::error('Error al actualizar usuario', ['user_id' => $usuario->id, 'error' => $e->getMessage()]);
            return back()->withInput()->withErrors(['general' => 'No se pudo actualizar el usuario. Intenta de nuevo.']);
        }
    }

    public function destroy(User $usuario): RedirectResponse
    {
        if ($usuario->id === auth()->id()) {
            return back()->withErrors(['general' => 'No puedes eliminar tu propia cuenta.']);
        }

        try {
            $nombre = $usuario->name;
            $usuario->delete(); // borrado lógico: conserva el historial de reservas.

            return redirect()->route('usuarios.index')
                ->with('success', "Usuario \"{$nombre}\" eliminado correctamente.");
        } catch (\Throwable $e) {
            Log::error('Error al eliminar usuario', ['user_id' => $usuario->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['general' => 'No se pudo eliminar el usuario. Intenta de nuevo.']);
        }
    }

    public function toggleActivo(User $usuario): RedirectResponse
    {
        if ($usuario->id === auth()->id()) {
            return back()->withErrors(['general' => 'No puedes desactivar tu propia cuenta.']);
        }

        try {
            $usuario->activo = ! $usuario->activo;
            $usuario->save();

            return back()->with('success', $usuario->activo
                ? "Cuenta de \"{$usuario->name}\" activada."
                : "Cuenta de \"{$usuario->name}\" desactivada.");
        } catch (\Throwable $e) {
            Log::error('Error al cambiar estado de usuario', ['user_id' => $usuario->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['general' => 'No se pudo cambiar el estado de la cuenta.']);
        }
    }
}
