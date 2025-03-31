<?php

namespace App\Controller;

use App\Dto\LoginRequest;
use App\Dto\LoginResponse;
use App\Dto\RefreshTokenRequest;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[AsController]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * @throws JWTEncodeFailureException
     */
    #[Route('/api/v1/auth/{roleName}/login', name: 'auth_login', requirements: ['roleName' => 'admin|user'], methods: ['POST'])]
    public function login(
        #[MapRequestPayload] LoginRequest $loginRequest,
        string $roleName,
        UserRepository $userRepository
    ): Response {
        $user = $userRepository->findOneBy(['email' => $loginRequest->getEmail()]);

        if (!$user) {
            return new JsonResponse([
                'error' => 'Invalid input or missing account'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($user->getRole() !== UserRole::from($roleName)) {
            return new JsonResponse([
                'error' => 'Invalid role'
            ], Response::HTTP_BAD_REQUEST);
        }

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user);
        if (!$passwordHasher->verify($user->getPassword(), $loginRequest->getPassword())) {
            return new JsonResponse([
                'error' => 'Incorrect password'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tokenData = [
            'client_id' => $user->getId(),
            'role' => $user->getRole()->value
        ];

        $refreshToken = $this->jwtEncoder->encode($tokenData);
        $tokenData['refresh_token'] = $refreshToken;

        $accessToken = $this->jwtEncoder->encode($tokenData);

        $response = new LoginResponse();
        $response->setClientId($user->getId());
        $response->setAccessToken($accessToken);
        $response->setRefreshToken($refreshToken);

        return new JsonResponse($this->serializer->serialize($response, 'json'), Response::HTTP_OK, [], true);
    }

    #[Route('/api/v1/auth/{roleName}/access', name: 'auth_access', requirements: ['roleName' => 'admin|user'], methods: ['POST'])]
    public function refreshAccessToken(
        #[MapRequestPayload] RefreshTokenRequest $refreshTokenRequest,
        string $roleName,
        UserRepository $userRepository
    ): Response {
        try {
            $tokenData = $this->jwtEncoder->decode($refreshTokenRequest->getRefreshToken());
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Invalid refresh token'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($tokenData['client_id']) || !isset($tokenData['role'])) {
            return new JsonResponse([
                'error' => 'Invalid token data'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->find($tokenData['client_id']);
        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($user->getRole() !== UserRole::from($roleName)) {
            return new JsonResponse([
                'error' => 'Invalid role'
            ], Response::HTTP_BAD_REQUEST);
        }

        $newTokenData = [
            'client_id' => $user->getId(),
            'role' => $user->getRole()->value
        ];

        $newRefreshToken = $this->jwtEncoder->encode($newTokenData);
        $newTokenData['refresh_token'] = $newRefreshToken;

        $newAccessToken = $this->jwtEncoder->encode($newTokenData);

        $response = new LoginResponse();
        $response->setClientId($user->getId());
        $response->setAccessToken($newAccessToken);
        $response->setRefreshToken($newRefreshToken);

        return new JsonResponse($this->serializer->serialize($response, 'json'), Response::HTTP_OK, [], true);
    }
}
