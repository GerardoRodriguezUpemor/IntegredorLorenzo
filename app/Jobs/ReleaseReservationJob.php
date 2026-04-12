<?php

namespace App\Jobs;

use App\Domain\Booking\Repositories\GroupRepositoryInterface;
use App\Domain\Shared\Enums\ReservationStatus;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job con TTL de 5 minutos.
 * Si la Reservation no pasa a PAID en ese tiempo, libérarla y restaurar el cupo.
 */
class ReleaseReservationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $reservationId
    ) {}

    public function handle(GroupRepositoryInterface $groupRepository): void
    {
        $reservation = Reservation::find($this->reservationId);

        if (!$reservation) {
            Log::warning("ReleaseReservationJob: Reservación {$this->reservationId} no encontrada.");
            return;
        }

        // Solo liberar si sigue en PENDING
        if ($reservation->status !== ReservationStatus::PENDING->value) {
            Log::info("ReleaseReservationJob: Reservación {$this->reservationId} ya no está PENDING (status: {$reservation->status}).");
            return;
        }

        // Cancelar la reservación
        $reservation->update([
            'status' => ReservationStatus::CANCELLED->value,
        ]);

        // Liberar el asiento en el grupo
        $group = $reservation->group;
        if ($group) {
            $groupRepository->releaseSeat($group);
        }

        // Remover el voto de la reserva expirada
        if ($reservation->schedule_option_id) {
            $option = \App\Models\ScheduleOption::find($reservation->schedule_option_id);
            if ($option && $option->vote_count > 0) {
                $option->vote_count -= 1;
                $option->save();
            }
        }

        Log::info("ReleaseReservationJob: Reservación {$this->reservationId} expirada y cupo liberado.");
    }
}
