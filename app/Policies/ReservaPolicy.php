<?php

namespace App\Policies;

use App\Models\Reserva;
use App\Models\User;

class ReservaPolicy
{
    public function view(User $user, Reserva $reserva): bool
    {
        return $user->activo && ($user->esStaff() || $reserva->user_id === $user->id);
    }

    public function update(User $user, Reserva $reserva): bool
    {
        return $this->view($user, $reserva);
    }

    public function manage(User $user, Reserva $reserva): bool
    {
        return $user->activo && $user->esStaff();
    }
}
