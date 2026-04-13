<?php

namespace App\Http\Controllers\Api\Student;

use App\Domain\Booking\Exceptions\GroupFullException;
use App\Domain\Booking\Repositories\GroupRepositoryInterface;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Shared\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ReleaseReservationJob;
use App\Models\Group;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly PricingEngine $pricingEngine,
    ) {}

    /**
     * GET /api/v1/groups/{groupId}/pricing
     * Calcular precio actual del grupo.
     */
    public function pricing(Request $request, string $groupId): JsonResponse
    {
        $group = Group::with('course.teacher')->find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado.', 'status' => 'error'], 404);
        }

        if ($group->isFull()) {
            return response()->json([
                'data' => [
                    'group' => ['id' => $groupId, 'name' => $group->name, 'status' => 'FULL'],
                    'pricing' => null,
                ],
                'message' => 'Este grupo está lleno.',
                'status' => 'success',
            ]);
        }

        $nextPosition = $group->current_count + 1;
        $priceBreakdown = $this->pricingEngine->calculate($nextPosition);

        return response()->json([
            'data' => [
                'group' => [
                    'id' => $groupId,
                    'name' => $group->name,
                    'course' => $group->course->name ?? 'N/A',
                    'teacher' => $group->course->teacher->name ?? 'N/A',
                    'current_count' => $group->current_count,
                    'max_capacity' => $group->max_capacity,
                    'available_seats' => $group->availableSeats(),
                    'your_position' => $nextPosition,
                ],
                'pricing' => $priceBreakdown->toArray(),
            ],
            'message' => "Precio para el alumno #{$nextPosition}.",
            'status' => 'success',
        ]);
    }

    /**
     * POST /api/v1/groups/{groupId}/reserve
     * Reservar asiento (5 min TTL).
     */
    public function reserve(Request $request, string $groupId): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'schedule_option_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
                'status' => 'error',
            ], 422);
        }

        $group = Group::find($groupId);

        if (!$group) {
            return response()->json(['message' => 'Grupo no encontrado.', 'status' => 'error'], 404);
        }

        $option = \App\Models\ScheduleOption::where('_id', $request->schedule_option_id)
            ->where('group_id', $groupId)
            ->first();

        if (!$option) {
            return response()->json(['message' => 'Opción de horario no encontrada para este grupo.', 'status' => 'error'], 404);
        }

        $user = $request->attributes->get('authenticated_user');

        try {
            $reservation = $this->groupRepository->reserveSeat($group, $user, $option);

            // Despachar job para liberar si no se paga en 5 minutos
            ReleaseReservationJob::dispatch((string) $reservation->_id)
                ->delay(now()->addMinutes(5));

            return response()->json([
                'data' => [
                    'reservation_id' => (string) $reservation->_id,
                    'group_id' => $reservation->group_id,
                    'status' => $reservation->status,
                    'frozen_price' => $reservation->frozen_price,
                    'pricing' => $reservation->price_breakdown,
                    'expires_at' => $reservation->expires_at->toIso8601String(),
                ],
                'message' => 'Reservación creada. Tienes 5 minutos para confirmar el pago.',
                'status' => 'success',
            ], 201);
        } catch (GroupFullException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
            ], 409);
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
            ], 409);
        }
    }

    /**
     * PATCH /api/v1/reservations/{reservationId}/confirm
     * Confirmar pago (PENDING → PAID).
     */
    public function confirm(Request $request, string $reservationId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');

        $reservation = Reservation::where('_id', $reservationId)
            ->where('user_id', (string) $user->_id)
            ->first();

        if (!$reservation) {
            return response()->json(['message' => 'Reservación no encontrada.', 'status' => 'error'], 404);
        }

        if ($reservation->status !== ReservationStatus::PENDING->value) {
            return response()->json([
                'message' => "La reservación no está pendiente (status: {$reservation->status}).",
                'status' => 'error',
            ], 409);
        }

        if ($reservation->isExpired()) {
            return response()->json([
                'message' => 'La reservación ha expirado. Debes crear una nueva.',
                'status' => 'error',
            ], 410);
        }

        $reservation->update([
            'status' => ReservationStatus::PAID->value,
            'paid_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'reservation_id' => (string) $reservation->_id,
                'status' => 'PAID',
                'frozen_price' => $reservation->frozen_price,
                'paid_at' => $reservation->paid_at->toIso8601String(),
            ],
            'message' => '¡Pago confirmado exitosamente! Ya estás inscrito.',
            'status' => 'success',
        ]);
    }
}
