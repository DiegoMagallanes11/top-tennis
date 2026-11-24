<?php

namespace App\Http\Middleware;

use App\Models\BookingEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistrarAdministracion
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->esStaff() || $request->isMethod('GET') || $request->routeIs('logout', 'password.*', 'profile.*')) {
            return $next($request);
        }

        return DB::transaction(function () use ($request, $next) {
            $response = $next($request);
            if ($response->getStatusCode() < 400 && ! $request->session()->has('errors')) {
                BookingEvent::create(['actor_id' => $request->user()->id, 'accion' => 'administracion:'.$request->route()->getName(),
                    'datos' => ['metodo' => $request->method()]]);
            }

            return $response;
        });
    }
}
