<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduleVote extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'schedule_votes';

    protected $fillable = [
        'user_id',
        'group_id',
        'schedule_option_id',
    ];

    /**
     * El estudiante que votó.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La opción de fecha votada.
     */
    public function scheduleOption()
    {
        return $this->belongsTo(ScheduleOption::class);
    }

    /**
     * El grupo asociado.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
