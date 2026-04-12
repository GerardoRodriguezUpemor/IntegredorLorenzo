<?php

namespace App\Domain\Shared\Enums;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case TEACHER = 'TEACHER';
    case STUDENT = 'STUDENT';
}
