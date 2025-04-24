<?php

namespace App\Service;

use App\Dto\LoginRequestDto;
use App\Dto\LoginResponseDto;
use App\Dto\RefreshTokenRequestDto;
use App\Entity\RefreshToken;
use App\Entity\User;
use App\Enum\UserRole;
use App\Exception\ServiceException;
use App\Repository\UserRepository;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AuthService
{
    private const ACCESS_TOKEN_EXPIRATION_DAYS = 30;
    private const REFRESH_TOKEN_EXPIRATION_DAYS = 7;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param LoginRequestDto $loginRequest
     * @param string $roleName
     * @return LoginResponseDto
     * @throws DateMalformedStringException
     * @throws JWTEncodeFailureException
     */
    public function login(LoginRequestDto $loginRequest, string $roleName): LoginResponseDto
    {
        $user = $this->userRepository->findOneBy(['email' => $loginRequest->getEmail()]);
        if (!$user) {
            throw new ServiceException('Invalid input or missing account');
        }
        if ($user->getRole() !== UserRole::from($roleName)) {
            throw new ServiceException('Invalid role');
        }
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user);
        if (!$passwordHasher->verify($user->getPassword(), $loginRequest->getPassword())) {
            throw new ServiceException('Incorrect password');
        }

        try {
            $refreshTokenExpirationDate = $this->getRefreshTokenExpirationDate();
            $refreshToken = $this->generateRefreshToken($user, $refreshTokenExpirationDate);
            $this->storeRefreshToken($user, $refreshToken, $refreshTokenExpirationDate);

            return new LoginResponseDto(
                $user->getId(),
                $this->generateAccessToken($user),
                $refreshToken
            );
        } catch (Exception $e) {
            $this->logger->error('Error whole tokens generate', ['exception' => $e->getMessage()]);
            throw new ServiceException('Login failed');
        }
    }

    public function refreshAccessToken(RefreshTokenRequestDto $refreshTokenRequest, string $roleName): LoginResponseDto
    {
        try {
            $tokenData = $this->jwtEncoder->decode($refreshTokenRequest->getRefreshToken());
        } catch (JWTDecodeFailureException $e) {
            throw new ServiceException('Invalid refresh token');
        }
        if (!isset($tokenData['user_id']) || !isset($tokenData['role']) || !isset($tokenData['username'])) {
            throw new ServiceException('Invalid token data');
        }
        $refreshToken = $this->entityManager->getRepository(RefreshToken::class)->findOneBy([
            'token' => $refreshTokenRequest->getRefreshToken()
        ]);
        if (!$refreshToken) {
            throw new ServiceException('Refresh token not found');
        }
        if ($refreshToken->getInvalidationDate() < new DateTimeImmutable()) {
            throw new ServiceException('Refresh token expired');
        }
        if ($refreshToken->getRole() !== $roleName) {
            throw new ServiceException('Invalid role');
        }
        $user = $this->userRepository->find($tokenData['user_id']);
        if (!$user) {
            throw new ServiceException('User not found');
        }

        try {
            $refreshTokenExpirationDate = $this->getRefreshTokenExpirationDate();
            $newRefreshToken = $this->generateRefreshToken($user, $refreshTokenExpirationDate);

            $refreshToken->setToken($newRefreshToken);
            $refreshToken->setInvalidationDate($refreshTokenExpirationDate);
            $this->entityManager->persist($refreshToken);
            $this->entityManager->flush();

            return new LoginResponseDto(
                $user->getId(),
                $this->generateAccessToken($user),
                $newRefreshToken
            );
        } catch (Exception $e) {
            $this->logger->error('Error whole tokens generate', ['exception' => $e->getMessage()]);
            throw new ServiceException('Login failed');
        }
    }

    public function logout(User $user, string $roleName): void
    {
        if ($user->getRole() !== UserRole::from($roleName)) {
            throw new \InvalidArgumentException('Invalid role');
        }
        $refreshToken = $this->entityManager->getRepository(RefreshToken::class)->findOneBy([
            'email' => $user->getEmail()
        ]);
        if ($refreshToken) {
            $this->entityManager->remove($refreshToken);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws DateMalformedStringException
     * @throws JWTEncodeFailureException
     */
    private function generateRefreshToken(object $user, DateTimeImmutable $expiration): string
    {
        $tokenData = [
            'user_id' => $user->getId(),
            'role' => $user->getRole()->name,
            'username' => $user->getEmail(),
            'exp' => $expiration->getTimestamp(),
        ];

        return $this->jwtEncoder->encode($tokenData);
    }

    /**
     * @throws DateMalformedStringException
     * @throws JWTEncodeFailureException
     */
    private function generateAccessToken(object $user): string
    {
        $expiration = new DateTimeImmutable()->modify('+' . self::ACCESS_TOKEN_EXPIRATION_DAYS . ' days');
        $tokenData = [
            'user_id' => $user->getId(),
            'role' => $user->getRole()->name,
            'username' => $user->getEmail(),
            'exp' => $expiration->getTimestamp(),
        ];

        return $this->jwtEncoder->encode($tokenData);
    }

    private function storeRefreshToken(object $user, string $refreshToken, DateTimeImmutable $expiration): void
    {
        $refreshTokenEntity = new RefreshToken(
            $refreshToken,
            $expiration,
            $user->getEmail(),
            $user->getRole()
        );
        $this->entityManager->persist($refreshTokenEntity);
        $this->entityManager->flush();
    }

    private function getRefreshTokenExpirationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable()->modify('+' . self::REFRESH_TOKEN_EXPIRATION_DAYS . ' days');
    }
}
