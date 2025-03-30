<?php

namespace App\Entity;

enum UserRoles: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
}
