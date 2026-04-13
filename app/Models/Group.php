<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use MongoDB\Laravel\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'groups';

    public const MAX_CAPACITY = 5;

    protected $fillable = [
        'course_id',
        'name',
        'status',
        'max_capacity',
        'current_count',
    ];

    protected $casts = [
        'max_capacity' => 'integer',
        'current_count' => 'integer',
    ];

    protected $attributes = [
        'status' => 'OPEN',
        'max_capacity' => 5,
        'current_count' => 0,
    ];

    /**
     * El curso al que pertenece este grupo.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Las reservaciones en este grupo.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Las opciones de fecha propuestas.
     */
    public function scheduleOptions()
    {
        return $this->hasMany(ScheduleOption::class);
    }

    /**
     * ¿El grupo está lleno?
     */
    public function isFull(): bool
    {
        return $this->current_count >= $this->max_capacity;
    }

    /**
     * Asientos disponibles.
     */
    public function availableSeats(): int
    {
        return max(0, $this->max_capacity - $this->current_count);
    }
}
