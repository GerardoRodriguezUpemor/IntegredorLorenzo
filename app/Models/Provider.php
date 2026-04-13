<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Provider extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'providers';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'specialty',
        'phone',
    ];

    /**
     * El usuario (cuenta) de este proveedor.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Los servicios/cursos de este proveedor.
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
