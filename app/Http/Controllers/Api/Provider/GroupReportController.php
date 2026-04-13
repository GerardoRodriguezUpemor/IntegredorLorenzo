<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Controller;
use App\Infrastructure\Pdf\GroupReportPdfGenerator;
use App\Models\Group;
use App\Models\Provider;
use Illuminate\Http\Request;

class GroupReportController extends Controller
{
    public function __construct(
        private readonly GroupReportPdfGenerator $pdfGenerator,
    ) {}

    /**
     * GET /api/v1/provider/groups/{groupId}/report
     */
    public function download(Request $request, string $groupId)
    {
        $user = $request->attributes->get('authenticated_user');
        $provider = Provider::where('user_id', (string) $user->_id)->first();

        $group = Group::with('course')->find($groupId);

        if (!$group || $group->course->provider_id !== (string) $provider->_id) {
            return response()->json(['message' => 'Grupo no encontrado o no pertenece a usted.', 'status' => 'error'], 404);
        }

        if ($group->current_count < 5) {
            return response()->json(['message' => 'El reporte solo está disponible cuando el grupo está lleno.', 'status' => 'error'], 409);
        }

        $pdf = $this->pdfGenerator->generate($group);
        
        $filename = 'reporte_grupo_' . str_replace(' ', '_', strtolower($group->name)) . '.pdf';

        return $pdf->download($filename);
    }
}
