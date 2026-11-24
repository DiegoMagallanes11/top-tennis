<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use Illuminate\View\View;

class CanchaPublicoController extends Controller
{
    public function index(): View
    {
        $canchas = Cancha::where('estado_mantenimiento', 'operativa')
            ->orderBy('nombre')
            ->get();

        return view('canchas.publico', compact('canchas'));
    }
}
