<?php

namespace App\Domain\Booking\Exceptions;

class GroupFullException extends \RuntimeException
{
    public function __construct(string $message = 'El grupo ha alcanzado su capacidad máxima de 5 alumnos.')
    {
        parent::__construct($message);
    }
}
