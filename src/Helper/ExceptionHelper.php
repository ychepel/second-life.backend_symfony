<?php

namespace App\Helper;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ExceptionHelper
{
    public static function throwValidationException(string $message, string $fieldName, mixed $rejectedValue): void
    {
        $violations = new ConstraintViolationList();

        $violations->add(new ConstraintViolation(
            $message,
            null,
            [],
            null,
            $fieldName,
            $rejectedValue
        ));

        $validationException = new ValidationFailedException('Error', $violations);
        throw new HttpException(Response::HTTP_BAD_REQUEST, 'Validation Failed', $validationException);
    }
}