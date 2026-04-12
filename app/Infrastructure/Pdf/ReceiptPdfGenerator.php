<?php

namespace App\Infrastructure\Pdf;

use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptPdfGenerator
{
    /**
     * Genera un PDF de recibo para una reservación pagada.
     *
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generate(Reservation $reservation)
    {
        $reservation->load(['user', 'group.course.teacher']);

        $data = [
            'reservation' => $reservation,
            'student' => $reservation->user,
            'group' => $reservation->group,
            'course' => $reservation->group->course,
            'teacher' => $reservation->group->course->teacher,
            'generated_at' => now()->format('d/m/Y H:i:s'),
        ];

        return Pdf::loadView('pdf.receipt', $data)
            ->setPaper('letter')
            ->setOption('defaultFont', 'sans-serif');
    }
}
