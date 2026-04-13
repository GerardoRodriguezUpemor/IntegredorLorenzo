<?php

namespace App\Infrastructure\Pdf;

use App\Models\Group;
use Barryvdh\DomPDF\Facade\Pdf;

class GroupReportPdfGenerator
{
    /**
     * Genera un reporte financiero de un grupo para el profesor.
     *
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generate(Group $group)
    {
        $group->load(['course.teacher', 'reservations.user']);

        // Filtrar solo reservaciones pagadas
        $paidReservations = $group->reservations->filter(fn($r) => $r->status === 'PAID');
        $totalRevenue = $paidReservations->sum('frozen_price');

        $data = [
            'group' => $group,
            'course' => $group->course,
            'teacher' => $group->course->teacher,
            'reservations' => $paidReservations,
            'totalRevenue' => $totalRevenue,
            'generated_at' => now()->format('d/m/Y H:i:s'),
        ];

        return Pdf::loadView('pdf.group_report', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'sans-serif');
    }
}
