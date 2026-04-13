<?php

namespace App\Models;

use MongoDB\Laravel\Auth\User as MongoUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends MongoUser
{
    use HasFactory, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Perfil de teacher (si aplica).
     */
    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * Reservaciones de este usuario (como estudiante).
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * ¿Es admin?
     */
    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    /**
     * ¿Es teacher?
     */
    public function isTeacher(): bool
    {
        return $this->role === 'TEACHER';
    }

    /**
     * ¿Es estudiante?
     */
    public function isStudent(): bool
    {
        return $this->role === 'STUDENT';
    }
}
