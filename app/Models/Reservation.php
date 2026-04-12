<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'reservations';

    protected $fillable = [
        'user_id',
        'group_id',
        'schedule_option_id',
        'status',
        'frozen_price',
        'price_breakdown',
        'expires_at',
        'paid_at',
    ];

    protected $casts = [
        'frozen_price' => 'float',
        'price_breakdown' => 'array',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * El estudiante que hizo la reservación.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * El grupo reservado.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * ¿La reservación ha expirado?
     */
    public function isExpired(): bool
    {
        return $this->status === 'PENDING' && $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * ¿Está pagada?
     */
    public function isPaid(): bool
    {
        return $this->status === 'PAID';
    }
}
