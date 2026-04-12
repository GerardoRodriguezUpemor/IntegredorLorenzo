<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'audit_logs';

    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    /**
     * El admin que realizó la acción.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Helper para registrar una acción.
     */
    public static function record(string $adminId, string $action, string $targetType, string $targetId, array $details = []): self
    {
        return self::create([
            'admin_id' => $adminId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
        ]);
    }
}
