<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'teachers';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'specialty',
    ];

    /**
     * El usuario (cuenta) de este teacher.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Los cursos de este teacher.
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
