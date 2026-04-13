<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Infrastructure\Pdf\ReceiptPdfGenerator;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __construct(
        private readonly ReceiptPdfGenerator $pdfGenerator,
    ) {}

    /**
     * GET /api/v1/reservations/{reservationId}/receipt
     * Descargar recibo PDF.
     */
    public function download(Request $request, string $reservationId)
    {
        $user = $request->attributes->get('authenticated_user');

        $reservation = Reservation::where('_id', $reservationId)
            ->where('user_id', (string) $user->_id)
            ->where('status', 'PAID')
            ->first();

        if (!$reservation) {
            return response()->json([
                'message' => 'Reservación no encontrada o no está pagada.',
                'status' => 'error',
            ], 404);
        }

        $pdf = $this->pdfGenerator->generate($reservation);

        $filename = 'recibo_ep4_' . substr((string) $reservation->_id, -8) . '.pdf';

        return $pdf->download($filename);
    }
}
