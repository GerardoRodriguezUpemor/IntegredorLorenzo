<?php

namespace App\Domain\Booking\Exceptions;

class ReservationExpiredException extends \RuntimeException
{
    public function __construct(string $message = 'La reservación ha expirado. El tiempo límite de 5 minutos ha pasado.')
    {
        parent::__construct($message);
    }
}
