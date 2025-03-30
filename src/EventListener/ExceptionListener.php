<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION, method: 'onKernelException', priority: 10)]
class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof HttpException || !$exception->getPrevious() instanceof ValidationFailedException) {
            return;
        }

        /** @var ValidationFailedException $validationException */
        $validationException = $exception->getPrevious();
        $violations = $validationException->getViolations();

        $errors = [];
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $errors[] = [
                'field'         => $this->formatPropertyPath($violation->getPropertyPath()),
                'rejectedValue' => $violation->getInvalidValue(),
                'message'       => $violation->getMessage(),
            ];
        }

        $response = new JsonResponse(
            ['errors' => $errors],
            Response::HTTP_BAD_REQUEST,
            $exception->getHeaders()
        );

        $event->setResponse($response);
    }

    private function formatPropertyPath(string $propertyPath): string
    {
        return preg_replace('/\[|\]/', '', $propertyPath);
    }
}
