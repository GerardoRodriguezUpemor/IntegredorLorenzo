<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduleOption extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'schedule_options';

    protected $fillable = [
        'group_id',
        'proposed_date',
        'vote_count',
    ];

    protected $casts = [
        'proposed_date' => 'datetime',
        'vote_count' => 'integer',
    ];

    protected $attributes = [
        'vote_count' => 0,
    ];

    /**
     * El grupo al que pertenece esta opción.
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Los votos para esta opción.
     */
    public function votes()
    {
        return $this->hasMany(ScheduleVote::class);
    }
}
