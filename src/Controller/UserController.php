<?php

namespace App\Controller;

use App\Dto\UserRegistrationRequestDto;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route(path: '/api/v1/users/register', name: 'register', methods: ['POST'])]
    public function register(#[MapRequestPayload] UserRegistrationRequestDto $request, UserService $userService): JsonResponse
    {
        $response = $userService->createUser($request);

        return $this->json($response, Response::HTTP_CREATED);
    }
}
