<?php

namespace App\Controller;

use App\Dto\LoginRequest;
use App\Dto\LoginResponse;
use App\Dto\RefreshTokenRequest;
use App\Entity\RefreshToken;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
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
    private const ACCESS_TOKEN_EXPIRATION_DAYS = 30;
    private const REFRESH_TOKEN_EXPIRATION_DAYS = 7;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly SerializerInterface $serializer
    ) {
    }

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

        $now = new DateTimeImmutable();
        $accessTokenExpiration = $now->modify('+' . self::ACCESS_TOKEN_EXPIRATION_DAYS . ' days');
        $refreshTokenExpiration = $now->modify('+' . self::REFRESH_TOKEN_EXPIRATION_DAYS . ' days');

        $tokenData = [
            'client_id' => $user->getId(),
            'role' => $user->getRole()->value,
            'email' => $user->getEmail(),
            'exp' => $accessTokenExpiration->getTimestamp()
        ];

        $refreshToken = $this->jwtEncoder->encode($tokenData);

        $refreshTokenEntity = new RefreshToken(
            $refreshToken,
            $refreshTokenExpiration,
            $user->getEmail(),
            $user->getRole()
        );
        $this->entityManager->persist($refreshTokenEntity);
        $this->entityManager->flush();

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

        if (!isset($tokenData['client_id']) || !isset($tokenData['role']) || !isset($tokenData['email'])) {
            return new JsonResponse([
                'error' => 'Invalid token data'
            ], Response::HTTP_BAD_REQUEST);
        }

        $refreshToken = $this->entityManager->getRepository(RefreshToken::class)->findOneBy([
            'token' => $refreshTokenRequest->getRefreshToken()
        ]);

        if (!$refreshToken) {
            return new JsonResponse([
                'error' => 'Refresh token not found'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($refreshToken->getInvalidationDate() < new DateTimeImmutable()) {
            return new JsonResponse([
                'error' => 'Refresh token expired'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($refreshToken->getRole() !== $roleName) {
            return new JsonResponse([
                'error' => 'Invalid role'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->find($tokenData['client_id']);
        if (!$user) {
            return new JsonResponse([
                'error' => 'User not found'
            ], Response::HTTP_BAD_REQUEST);
        }

        $now = new DateTimeImmutable();
        $accessTokenExpiration = $now->modify('+' . self::ACCESS_TOKEN_EXPIRATION_DAYS . ' days');
        $refreshTokenExpiration = $now->modify('+' . self::REFRESH_TOKEN_EXPIRATION_DAYS . ' days');

        $newTokenData = [
            'client_id' => $user->getId(),
            'role' => $user->getRole()->value,
            'email' => $user->getEmail(),
            'exp' => $accessTokenExpiration->getTimestamp()
        ];

        $newRefreshToken = $this->jwtEncoder->encode($newTokenData);

        $refreshToken->setToken($newRefreshToken);
        $refreshToken->setInvalidationDate($refreshTokenExpiration);
        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        $newAccessToken = $this->jwtEncoder->encode($newTokenData);

        $response = new LoginResponse();
        $response->setClientId($user->getId());
        $response->setAccessToken($newAccessToken);
        $response->setRefreshToken($newRefreshToken);

        return new JsonResponse($this->serializer->serialize($response, 'json'), Response::HTTP_OK, [], true);
    }
}
