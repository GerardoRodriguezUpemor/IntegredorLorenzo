<?php

namespace App\Domain\Booking\Repositories;

use App\Models\Group;
use App\Models\User;
use App\Models\Reservation;

interface GroupRepositoryInterface
{
    /**
     * Reserva un asiento en un grupo de forma atómica.
     * Debe prevenir race conditions.
     *
     * @throws \App\Domain\Booking\Exceptions\GroupFullException
     */
    public function reserveSeat(Group $group, User $user, \App\Models\ScheduleOption $option): Reservation;

    /**
     * Libera un asiento de forma atómica (cuando expira la reservación).
     */
    public function releaseSeat(Group $group): void;
}
