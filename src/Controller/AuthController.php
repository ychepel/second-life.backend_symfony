<?php

namespace App\Controller;

use App\Dto\LoginRequestDto;
use App\Dto\RefreshTokenRequestDto;
use App\Entity\User;
use App\Enum\UserRole;
use App\Exception\ServiceException;
use App\Helper\MultiplyRolesExpression;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly Security $security,
    ) {
    }

    #[Route('/{roleName}/login', name: 'auth_login', requirements: ['roleName' => 'admin|user'], methods: ['POST'])]
    public function login(
        #[MapRequestPayload] LoginRequestDto $loginRequest,
        string $roleName,
    ): Response {
        try {
            $loginResponse = $this->authService->login($loginRequest, $roleName);
        } catch (ServiceException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
        $now = new \DateTimeImmutable();
        $accessTokenExpiration = $now->modify('+30 days');
        $response = $this->json($loginResponse);
        $response->headers->setCookie(
            new Cookie(
                'access_token',
                $loginResponse->getAccessToken(),
                $accessTokenExpiration,
                '/',
                null,
                true,
                true,
                false,
                'strict'
            )
        );

        return $response;
    }

    #[Route('/{roleName}/access', name: 'auth_access', requirements: ['roleName' => 'admin|user'], methods: ['POST'])]
    public function refreshAccessToken(
        #[MapRequestPayload] RefreshTokenRequestDto $refreshTokenRequest,
        string $roleName,
    ): Response {
        try {
            $loginResponse = $this->authService->refreshAccessToken($refreshTokenRequest, $roleName);
        } catch (ServiceException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
        $now = new \DateTimeImmutable();
        $accessTokenExpiration = $now->modify('+30 days');
        $response = $this->json($loginResponse);
        $response->headers->setCookie(
            new Cookie(
                'access_token',
                $loginResponse->getAccessToken(),
                $accessTokenExpiration,
                '/',
                null,
                true,
                true,
                false,
                'strict'
            )
        );

        return $response;
    }

    #[Route('/{roleName}/logout', name: 'auth_logout', requirements: ['roleName' => 'admin|user'], methods: ['GET'])]
    #[IsGranted(new MultiplyRolesExpression(UserRole::ROLE_USER, UserRole::ROLE_ADMIN))]
    public function logout(string $roleName): Response
    {
        try {
            /** @var User $user */
            $user = $this->security->getUser();
            $this->authService->logout($user, $roleName);
            $response = $this->json(['message' => 'Successfully logged out']);
            $response->headers->clearCookie('access_token');

            return $response;
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Logout failed: '.$e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
