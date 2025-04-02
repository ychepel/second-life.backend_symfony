<?php

namespace App\Controller;

use App\Dto\LoginRequest;
use App\Dto\LoginResponse;
use App\Dto\RefreshTokenRequest;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Enum\UserRole;
use App\Helper\MultiplyRolesExpression;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/auth')]
class AuthController extends AbstractController
{
    private const ACCESS_TOKEN_EXPIRATION_DAYS = 30;
    private const REFRESH_TOKEN_EXPIRATION_DAYS = 7;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory
    ) {
    }

    #[Route('/{roleName}/login', name: 'auth_login', requirements: ['roleName' => 'admin|user'], methods: ['POST'])]
    public function login(
        #[MapRequestPayload] LoginRequest $loginRequest,
        string $roleName,
        UserRepository $userRepository
    ): Response {
        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => $loginRequest->getEmail()]);

        if (!$user) {
            return $this->json([
                'error' => 'Invalid input or missing account'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($user->getRole() !== UserRole::from($roleName)) {
            return $this->json([
                'error' => 'Invalid role'
            ], Response::HTTP_BAD_REQUEST);
        }

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user);
        if (!$passwordHasher->verify($user->getPassword(), $loginRequest->getPassword())) {
            return $this->json([
                'error' => 'Incorrect password'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $now = new DateTimeImmutable();
        $accessTokenExpiration = $now->modify('+' . self::ACCESS_TOKEN_EXPIRATION_DAYS . ' days');
        $refreshTokenExpiration = $now->modify('+' . self::REFRESH_TOKEN_EXPIRATION_DAYS . ' days');

        $tokenData = [
            'user_id' => $user->getId(),
            'role' => $user->getRole()->name,
            'username' => $user->getEmail()
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

        $responseData = new LoginResponse();
        $responseData->setClientId($user->getId());
        $responseData->setAccessToken($accessToken);
        $responseData->setRefreshToken($refreshToken);

        $response = $this->json($responseData);

        $response->headers->setCookie(
            new Cookie(
                'access_token',
                $accessToken,
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
        #[MapRequestPayload] RefreshTokenRequest $refreshTokenRequest,
        string $roleName,
        UserRepository $userRepository
    ): Response {
        try {
            $tokenData = $this->jwtEncoder->decode($refreshTokenRequest->getRefreshToken());
        } catch (JWTDecodeFailureException $e) {
            return $this->json([
                'error' => 'Invalid refresh token'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($tokenData['user_id']) || !isset($tokenData['role']) || !isset($tokenData['username'])) {
            return $this->json([
                'error' => 'Invalid token data'
            ], Response::HTTP_BAD_REQUEST);
        }

        $refreshToken = $this->entityManager->getRepository(RefreshToken::class)->findOneBy([
            'token' => $refreshTokenRequest->getRefreshToken()
        ]);

        if (!$refreshToken) {
            return $this->json([
                'error' => 'Refresh token not found'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($refreshToken->getInvalidationDate() < new DateTimeImmutable()) {
            return $this->json([
                'error' => 'Refresh token expired'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($refreshToken->getRole() !== $roleName) {
            return $this->json([
                'error' => 'Invalid role'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->find($tokenData['user_id']);
        if (!$user) {
            return $this->json([
                'error' => 'User not found'
            ], Response::HTTP_BAD_REQUEST);
        }

        $now = new DateTimeImmutable();
        //TODO: make token contents different and check expiration dates
        $accessTokenExpiration = $now->modify('+' . self::ACCESS_TOKEN_EXPIRATION_DAYS . ' days');
        $refreshTokenExpiration = $now->modify('+' . self::REFRESH_TOKEN_EXPIRATION_DAYS . ' days');

        $newTokenData = [
            'user_id' => $user->getId(),
            'role' => $user->getRole(),
            'username' => $user->getEmail()
        ];

        $newRefreshToken = $this->jwtEncoder->encode($newTokenData);

        $refreshToken->setToken($newRefreshToken);
        $refreshToken->setInvalidationDate($refreshTokenExpiration);
        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        $newAccessToken = $this->jwtEncoder->encode($newTokenData);

        $responseData = new LoginResponse();
        $responseData->setClientId($user->getId());
        $responseData->setAccessToken($newAccessToken);
        $responseData->setRefreshToken($newRefreshToken);

        $response = $this->json($responseData);
        
        $response->headers->setCookie(
            new Cookie(
                'access_token',
                $newAccessToken,
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
    public function logout(string $roleName, Security $security): Response
    {
        try {
            /** @var User $user */
            $user = $security->getUser();

            if ($user->getRole() !== UserRole::from($roleName)) {
                throw new \Exception('Invalid role');
            }

            $refreshToken = $this->entityManager->getRepository(RefreshToken::class)->findOneBy([
                'email' => $user->getEmail()
            ]);

            if ($refreshToken) {
                $this->entityManager->remove($refreshToken);
                $this->entityManager->flush();
            }

            $response = $this->json(['message' => 'Successfully logged out']);
            $response->headers->clearCookie('access_token');

            return $response;
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Logout failed: ' . $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
