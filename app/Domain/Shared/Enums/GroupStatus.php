<?php

namespace App\Domain\Shared\Enums;

enum GroupStatus: string
{
    case OPEN = 'OPEN';
    case RESERVED = 'RESERVED';
    case FULL = 'FULL';
    case ACTIVE = 'ACTIVE';
}
