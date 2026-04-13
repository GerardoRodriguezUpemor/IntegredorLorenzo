<?php

namespace App\Domain\Shared\Enums;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case PROVIDER = 'PROVIDER';
    case CLIENT = 'CLIENT';
}
