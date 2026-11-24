<?php

namespace App\Providers;

use App\Models\Cancha;
use App\Models\Horario;
use App\Models\Tarifa;
use App\Policies\CanchaPolicy;
use App\Policies\HorarioPolicy;
use App\Policies\TarifaPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Reserva::class => \App\Policies\ReservaPolicy::class,
        Cancha::class  => CanchaPolicy::class,
        Tarifa::class  => TarifaPolicy::class,
        Horario::class => HorarioPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();
        \Illuminate\Validation\Rules\Password::defaults(fn () => app()->environment('production')
            ? \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()
            : \Illuminate\Validation\Rules\Password::min(8));
    }
}
