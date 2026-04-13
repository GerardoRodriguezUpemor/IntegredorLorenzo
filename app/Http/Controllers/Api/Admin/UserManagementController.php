<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    /**
     * GET /api/v1/admin/users
     */
    public function index(Request $request): JsonResponse
    {
        $role = $request->query('role');

        $query = User::query();

        if ($role) {
            $query->where('role', strtoupper($role));
        }

        $users = $query->orderBy('created_at', 'desc')->get()->map(fn($user) => [
            'id' => (string) $user->_id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active ?? true,
            'created_at' => $user->created_at,
        ]);

        return response()->json([
            'data' => $users,
            'message' => 'Lista de usuarios.',
            'status' => 'success',
        ]);
    }

    /**
     * PATCH /api/v1/admin/users/{userId}/toggle-status
     */
    public function toggleStatus(Request $request, string $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.', 'status' => 'error'], 404);
        }

        $currentStatus = $user->is_active ?? true;
        $user->update(['is_active' => !$currentStatus]);

        $admin = $request->attributes->get('authenticated_user');

        AuditLog::record(
            (string) $admin->_id,
            $user->is_active ? 'USER_ACTIVATED' : 'USER_DEACTIVATED',
            'User',
            $userId,
            ['user_name' => $user->name, 'user_email' => $user->email]
        );

        return response()->json([
            'data' => [
                'id' => (string) $user->_id,
                'name' => $user->name,
                'is_active' => $user->is_active,
            ],
            'message' => $user->is_active ? 'Usuario activado.' : 'Usuario desactivado.',
            'status' => 'success',
        ]);
    }

    /**
     * DELETE /api/v1/admin/users/{userId}
     */
    public function destroy(Request $request, string $userId): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.', 'status' => 'error'], 404);
        }

        // Bloquear eliminación del admin logueado por seguridad
        $admin = $request->attributes->get('authenticated_user');
        if ((string)$admin->_id === (string)$user->_id) {
            return response()->json(['message' => 'No puedes eliminarte a ti mismo.', 'status' => 'error'], 403);
        }

        $user->delete(); // Soft Delete

        AuditLog::record(
            (string) $admin->_id,
            'USER_DELETED_LOGICALLY',
            'User',
            $userId,
            ['user_name' => $user->name, 'user_email' => $user->email]
        );

        return response()->json([
            'message' => 'Usuario eliminado (borrado lógico) correctamente.',
            'status' => 'success',
        ]);
    }

    /**
     * GET /api/v1/admin/users/report
     */
    public function downloadReport(Request $request, \App\Infrastructure\Pdf\UserDirectoryPdfGenerator $generator)
    {
        $users = User::orderBy('name', 'asc')->get();
        $pdfContent = $generator->generate($users);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="directorio_usuarios_ep4.pdf"');
    }
}
