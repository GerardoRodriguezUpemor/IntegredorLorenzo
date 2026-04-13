<?php

namespace App\Infrastructure\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class UserDirectoryPdfGenerator
{
    public function generate(Collection $users): string
    {
        $data = [
            'users' => $users,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.user_directory', $data)
                  ->setPaper('a4', 'landscape');

        return $pdf->output();
    }
}
