<?php

namespace App\Helper;

use App\Enum\UserRole;
use Symfony\Component\ExpressionLanguage\Expression;

class MultiplyRolesExpression extends Expression
{
    /**
     * @param UserRole ...$roles
     */
    public function __construct(...$roles)
    {
        parent::__construct($this->generateRolesExpression(...$roles));
    }

    /**
     * @param UserRole ...$roles
     */
    private function generateRolesExpression(...$roles): string
    {
        $roles = array_map(static fn ($role) => $role->name, $roles);

        return implode(' or ', array_map(fn ($role) => "is_granted(\"$role\")", $roles));
    }
}