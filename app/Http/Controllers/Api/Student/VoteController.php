<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\ScheduleOption;
use App\Models\ScheduleVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VoteController extends Controller
{
    /**
     * GET /api/v1/groups/{groupId}/schedule
     * Ver fechas disponibles y estado de los votos.
     */
    public function schedule(Request $request, string $groupId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');

        $options = ScheduleOption::where('group_id', $groupId)
            ->orderBy('proposed_date')
            ->get()
            ->map(fn($opt) => [
                'id' => (string) $opt->_id,
                'proposed_date' => $opt->proposed_date,
                'vote_count' => $opt->vote_count,
            ]);

        // Verificar si el usuario ya votó
        $existingVote = ScheduleVote::where('user_id', (string) $user->_id)
            ->where('group_id', $groupId)
            ->first();

        return response()->json([
            'data' => [
                'options' => $options,
                'my_vote' => $existingVote ? (string) $existingVote->schedule_option_id : null,
            ],
            'message' => 'Fechas propuestas para la clase.',
            'status' => 'success',
        ]);
    }

    /**
     * POST /api/v1/groups/{groupId}/vote
     * Votar por una fecha. Solo PAID pueden votar. 1 voto por estudiante.
     */
    public function vote(Request $request, string $groupId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');

        $validator = Validator::make($request->all(), [
            'schedule_option_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Debe especificar schedule_option_id.',
                'errors' => $validator->errors(),
                'status' => 'error',
            ], 422);
        }

        // Verificar que el estudiante tiene reservación PAID en este grupo
        $hasPaid = Reservation::where('user_id', (string) $user->_id)
            ->where('group_id', $groupId)
            ->where('status', 'PAID')
            ->exists();

        if (!$hasPaid) {
            return response()->json([
                'message' => 'Solo puedes votar si tienes un pago confirmado en este grupo.',
                'status' => 'error',
            ], 403);
        }

        // Verificar que la opción existe y pertenece al grupo
        $option = ScheduleOption::where('_id', $request->schedule_option_id)
            ->where('group_id', $groupId)
            ->first();

        if (!$option) {
            return response()->json([
                'message' => 'Opción de fecha no encontrada para este grupo.',
                'status' => 'error',
            ], 404);
        }

        // Verificar si ya votó (puede cambiar su voto)
        $existingVote = ScheduleVote::where('user_id', (string) $user->_id)
            ->where('group_id', $groupId)
            ->first();

        if ($existingVote) {
            // Decrementar voto anterior
            ScheduleOption::where('_id', $existingVote->schedule_option_id)
                ->where('vote_count', '>', 0)
                ->decrement('vote_count');

            // Actualizar voto
            $existingVote->update([
                'schedule_option_id' => $request->schedule_option_id,
            ]);
        } else {
            // Nuevo voto
            ScheduleVote::create([
                'user_id' => (string) $user->_id,
                'group_id' => $groupId,
                'schedule_option_id' => $request->schedule_option_id,
            ]);
        }

        // Incrementar voto de la nueva opción
        $option->vote_count += 1;
        $option->save();

        return response()->json([
            'data' => [
                'voted_for' => [
                    'option_id' => (string) $option->_id,
                    'proposed_date' => $option->proposed_date,
                ],
            ],
            'message' => $existingVote ? 'Voto actualizado.' : '¡Voto registrado!',
            'status' => 'success',
        ]);
    }
}
