<?php

namespace App\Entity;

enum UserRole: string
{
    case USER = 'user';
    case ADMIN = 'admin';
}
