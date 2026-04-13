<?php

namespace App\Infrastructure\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use App\Models\User;
use Carbon\Carbon;

class AdminCourseReportPdfGenerator
{
    public function generate(Collection $courses): string
    {
        // Obtener nombres de proveedores para evitar N+1 en la vista
        $providerIds = $courses->pluck('provider_id')->unique();
        $userNames = User::whereIn('_id', $providerIds)->pluck('name', '_id');

        $data = [
            'courses' => $courses,
            'userNames' => $userNames,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.course_catalog_admin', $data)
                  ->setPaper('a4', 'landscape');

        return $pdf->output();
    }
}
