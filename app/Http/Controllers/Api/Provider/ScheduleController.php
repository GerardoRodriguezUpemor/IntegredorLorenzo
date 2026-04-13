<?php

namespace App\Http\Controllers\Api\Provider;

use App\Domain\Scheduling\VotingEngine;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\ScheduleOption;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly VotingEngine $votingEngine
    ) {}

    /**
     * POST /api/v1/provider/groups/{groupId}/schedule
     * Proponer 3-5 fechas para la clase.
     */
    public function store(Request $request, string $groupId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $provider = Provider::where('user_id', (string) $user->_id)->first();

        $group = Group::with('course')->find($groupId);

        if (!$group || (string) $group->course->provider_id !== (string) $provider->_id) {
            return response()->json(['message' => 'Grupo no encontrado o no te pertenece.', 'status' => 'error'], 404);
        }

        $validator = Validator::make($request->all(), [
            'dates' => 'required|array|min:3|max:5',
            'dates.*' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Debe proponer entre 3 y 5 fechas futuras.',
                'errors' => $validator->errors(),
                'status' => 'error',
            ], 422);
        }

        // Eliminar opciones anteriores si existen
        ScheduleOption::where('group_id', $groupId)->delete();

        // Crear nuevas opciones
        $options = [];
        foreach ($request->dates as $date) {
            $options[] = ScheduleOption::create([
                'group_id' => $groupId,
                'proposed_date' => $date,
                'vote_count' => 0,
            ]);
        }

        return response()->json([
            'data' => $options,
            'message' => count($options) . ' fechas propuestas exitosamente.',
            'status' => 'success',
        ], 201);
    }

    /**
     * GET /api/v1/provider/groups/{groupId}/votes
     */
    public function votes(Request $request, string $groupId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $provider = Provider::where('user_id', (string) $user->_id)->first();

        $group = Group::with('course')->find($groupId);

        if (!$group || (string) $group->course->provider_id !== (string) $provider->_id) {
            return response()->json(['message' => 'Grupo no encontrado o no te pertenece.', 'status' => 'error'], 404);
        }

        $options = $this->votingEngine->getOptionsWithVotes($groupId);

        return response()->json([
            'data' => [
                'group_id' => $groupId,
                'total_students' => $group->current_count,
                'options' => $options,
            ],
            'message' => 'Resultados de votación.',
            'status' => 'success',
        ]);
    }

    /**
     * GET /api/v1/provider/groups/{groupId}/winning-date
     */
    public function winningDate(Request $request, string $groupId): JsonResponse
    {
        $user = $request->attributes->get('authenticated_user');
        $provider = Provider::where('user_id', (string) $user->_id)->first();

        $group = Group::with('course')->find($groupId);

        if (!$group || (string) $group->course->provider_id !== (string) $provider->_id) {
            return response()->json(['message' => 'Grupo no encontrado o no te pertenece.', 'status' => 'error'], 404);
        }

        $winner = $this->votingEngine->getWinningDate($groupId);

        if (!$winner) {
            return response()->json([
                'data' => null,
                'message' => 'Aún no hay votos registrados.',
                'status' => 'success',
            ]);
        }

        return response()->json([
            'data' => [
                'winning_date' => $winner['option']->proposed_date,
                'total_votes' => $winner['votes'],
                'total_students' => $group->current_count,
            ],
            'message' => 'Fecha ganadora de la votación.',
            'status' => 'success',
        ]);
    }
}
