<?php

namespace App\Domain\Shared\Enums;

enum ReservationStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';
    case CANCELLED = 'CANCELLED';
}
