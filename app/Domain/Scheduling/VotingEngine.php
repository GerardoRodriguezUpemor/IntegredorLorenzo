<?php

namespace App\Domain\Scheduling;

use App\Models\ScheduleOption;
use App\Models\ScheduleVote;

/**
 * Motor de votación de fechas — lógica pura de dominio.
 */
class VotingEngine
{
    public const MIN_OPTIONS = 3;
    public const MAX_OPTIONS = 5;

    /**
     * Valida que el número de opciones propuestas sea válido.
     */
    public function validateOptionCount(int $count): void
    {
        if ($count < self::MIN_OPTIONS || $count > self::MAX_OPTIONS) {
            throw new \InvalidArgumentException(
                "Debe proponer entre " . self::MIN_OPTIONS . " y " . self::MAX_OPTIONS . " fechas. Recibido: {$count}"
            );
        }
    }

    /**
     * Determina la fecha ganadora de un grupo (la más votada).
     *
     * @param string $groupId
     * @return array|null ['option' => ScheduleOption, 'votes' => int]
     */
    public function getWinningDate(string $groupId): ?array
    {
        $winner = ScheduleOption::where('group_id', $groupId)
            ->orderByDesc('vote_count')
            ->first();

        if (!$winner || $winner->vote_count === 0) {
            return null;
        }

        return [
            'option' => $winner,
            'votes' => $winner->vote_count,
        ];
    }

    /**
     * Obtiene todas las opciones con sus votos para un grupo.
     */
    public function getOptionsWithVotes(string $groupId): array
    {
        return ScheduleOption::where('group_id', $groupId)
            ->orderByDesc('vote_count')
            ->get()
            ->toArray();
    }
}
