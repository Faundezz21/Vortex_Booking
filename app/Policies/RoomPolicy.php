<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

// politicas de autorizacion para lectura y gestion de salas
class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Room $room): bool
    {
        return $room->is_active || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Room $room): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->isAdmin();
    }
}
