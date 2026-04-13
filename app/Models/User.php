<?php

namespace App\Models;

use MongoDB\Laravel\Auth\User as MongoUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

use MongoDB\Laravel\Eloquent\SoftDeletes;

class User extends MongoUser
{
    use HasFactory, Notifiable, SoftDeletes;

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
     * Perfil de proveedor (si aplica).
     */
    public function provider()
    {
        return $this->hasOne(Provider::class);
    }

    /**
     * Reservaciones de este usuario (como cliente).
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
     * ¿Es proveedor?
     */
    public function isProvider(): bool
    {
        return $this->role === 'PROVIDER';
    }

    /**
     * ¿Es cliente?
     */
    public function isClient(): bool
    {
        return $this->role === 'CLIENT';
    }
}
