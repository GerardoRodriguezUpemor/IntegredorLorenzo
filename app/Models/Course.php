<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'courses';

    protected $fillable = [
        'name',
        'description',
        'teacher_id',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * El teacher que creó este curso.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Los grupos de este curso.
     */
    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    /**
     * El admin que aprobó el curso.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope: solo cursos aprobados (visibles en catálogo).
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'APPROVED');
    }

    /**
     * Scope: cursos pendientes de aprobación.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'PENDING_APPROVAL');
    }
}
