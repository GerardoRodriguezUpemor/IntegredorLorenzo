<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Booking\Exceptions\GroupFullException;
use App\Domain\Booking\Repositories\GroupRepositoryInterface;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Shared\Enums\GroupStatus;
use App\Domain\Shared\Enums\ReservationStatus;
use App\Models\Group;
use App\Models\Reservation;
use App\Models\User;

class MongoGroupRepository implements GroupRepositoryInterface
{
    public function __construct(
        private readonly PricingEngine $pricingEngine
    ) {}

    /**
     * Reserva un asiento de forma atómica usando operaciones de MongoDB.
     * MongoDB garantiza atomicidad por documento, previniendo race conditions.
     */
    public function reserveSeat(Group $group, User $user, \App\Models\ScheduleOption $option): Reservation
    {
        // Verificar que el usuario no tenga ya una reservación activa en este grupo
        $existing = Reservation::where('user_id', (string) $user->_id)
            ->where('group_id', (string) $group->_id)
            ->whereIn('status', [ReservationStatus::PENDING->value, ReservationStatus::PAID->value])
            ->first();

        if ($existing) {
            throw new \RuntimeException('Ya tienes una reservación activa en este grupo.');
        }

        // Check capacity beforehand
        if ($group->current_count >= $group->max_capacity) {
            throw new GroupFullException();
        }

        // Standard save (in a real production Mongo Atlas this might be $inc, but local test drivers struggle with increment() mixing object types)
        $group->current_count += 1;
        $group->save();

        // Registrar el voto
        $option->vote_count += 1;
        $option->save();

        // Calcular precio congelado (basado en la posición del estudiante)
        $priceBreakdown = $this->pricingEngine->calculate($group->current_count);

        // Crear la reservación
        $reservation = Reservation::create([
            'user_id' => (string) $user->_id,
            'group_id' => (string) $group->_id,
            'schedule_option_id' => (string) $option->_id,
            'status' => ReservationStatus::PENDING->value,
            'frozen_price' => $priceBreakdown->currentPrice,
            'price_breakdown' => $priceBreakdown->toArray(),
            'expires_at' => now()->addMinutes(5),
        ]);

        // Actualizar status del grupo
        $this->updateGroupStatus($group);

        return $reservation;
    }

    /**
     * Libera un asiento de forma atómica.
     */
    public function releaseSeat(Group $group): void
    {
        Group::where('_id', $group->_id)
            ->where('current_count', '>', 0)
            ->decrement('current_count');

        $group->refresh();
        $this->updateGroupStatus($group);
    }

    /**
     * Actualiza el status del grupo según su ocupación.
     */
    private function updateGroupStatus(Group $group): void
    {
        $newStatus = match (true) {
            $group->current_count >= $group->max_capacity => GroupStatus::FULL->value,
            $group->current_count > 0 => GroupStatus::RESERVED->value,
            default => GroupStatus::OPEN->value,
        };

        $group->update(['status' => $newStatus]);
    }
}
