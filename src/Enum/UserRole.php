<?php

namespace App\Enum;

enum UserRole: string
{
    case ROLE_USER = 'user';
    case ROLE_ADMIN = 'admin';
}
