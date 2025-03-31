<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends ControllerTest
{
    private const TEST_USER_DATA = [
        'email' => 'test@example.com',
        'password' => 'Qwerty!123',
        'firstName' => 'John',
        'lastName' => 'Doe',
    ];

    public function testLoginSuccess(): void
    {
        $passwordHasher = $this->client->getContainer()->get('security.user_password_hasher');
        
        $user = new User();
        $user->setEmail(self::TEST_USER_DATA['email']);
        $user->setPassword($passwordHasher->hashPassword($user, self::TEST_USER_DATA['password']));
        $user->setRole(UserRole::USER);
        $user->setFirstName(self::TEST_USER_DATA['firstName']);
        $user->setLastName(self::TEST_USER_DATA['lastName']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $requestData = [
            'email' => self::TEST_USER_DATA['email'],
            'password' => self::TEST_USER_DATA['password'],
        ];

        $response = $this->apiRequest('POST', '/api/v1/auth/user/login', $requestData);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('clientId', $responseData);
        $this->assertArrayHasKey('accessToken', $responseData);
        $this->assertArrayHasKey('refreshToken', $responseData);
    }

    public function testLoginWithInvalidEmail(): void
    {
        $requestData = [
            'email' => 'nonexistent@example.com',
            'password' => self::TEST_USER_DATA['password'],
        ];

        $response = $this->apiRequest('POST', '/api/v1/auth/user/login', $requestData);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid input or missing account', $responseData['error']);
    }

    public function testLoginWithIncorrectPassword(): void
    {
        $passwordHasher = $this->client->getContainer()->get('security.user_password_hasher');
        
        $user = new User();
        $user->setEmail(self::TEST_USER_DATA['email']);
        $user->setPassword($passwordHasher->hashPassword($user, self::TEST_USER_DATA['password']));
        $user->setRole(UserRole::USER);
        $user->setFirstName(self::TEST_USER_DATA['firstName']);
        $user->setLastName(self::TEST_USER_DATA['lastName']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $requestData = [
            'email' => self::TEST_USER_DATA['email'],
            'password' => 'wrong_password',
        ];

        $response = $this->apiRequest('POST', '/api/v1/auth/user/login', $requestData);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Incorrect password', $responseData['error']);
    }

    public function testLoginWithInvalidRole(): void
    {
        $passwordHasher = $this->client->getContainer()->get('security.user_password_hasher');
        
        $user = new User();
        $user->setEmail(self::TEST_USER_DATA['email']);
        $user->setPassword($passwordHasher->hashPassword($user, self::TEST_USER_DATA['password']));
        $user->setRole(UserRole::USER);
        $user->setFirstName(self::TEST_USER_DATA['firstName']);
        $user->setLastName(self::TEST_USER_DATA['lastName']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $requestData = [
            'email' => self::TEST_USER_DATA['email'],
            'password' => 'wrong_password',
        ];

        $response = $this->apiRequest('POST', '/api/v1/auth/admin/login', $requestData);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid role', $responseData['error']);
    }

    public function testRefreshAccessTokenSuccess(): void
    {
        $passwordHasher = $this->client->getContainer()->get('security.user_password_hasher');
        
        $user = new User();
        $user->setEmail(self::TEST_USER_DATA['email']);
        $user->setPassword($passwordHasher->hashPassword($user, self::TEST_USER_DATA['password']));
        $user->setRole(UserRole::USER);
        $user->setFirstName(self::TEST_USER_DATA['firstName']);
        $user->setLastName(self::TEST_USER_DATA['lastName']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $requestData = [
            'email' => self::TEST_USER_DATA['email'],
            'password' => self::TEST_USER_DATA['password'],
        ];

        $this->apiRequest('POST', '/api/v1/auth/user/login', $requestData);

        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);

        $refreshRequestData = [
            'refreshToken' => $loginResponse['refreshToken']
        ];

        $response = $this->apiRequest('POST', '/api/v1/auth/user/access', $refreshRequestData);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('clientId', $responseData);
        $this->assertArrayHasKey('accessToken', $responseData);
        $this->assertArrayHasKey('refreshToken', $responseData);
    }

    public function testRefreshAccessTokenWithInvalidToken(): void
    {
        $passwordHasher = $this->client->getContainer()->get('security.user_password_hasher');
        
        $user = new User();
        $user->setEmail(self::TEST_USER_DATA['email']);
        $user->setPassword($passwordHasher->hashPassword($user, self::TEST_USER_DATA['password']));
        $user->setRole(UserRole::USER);
        $user->setFirstName(self::TEST_USER_DATA['firstName']);
        $user->setLastName(self::TEST_USER_DATA['lastName']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $refreshRequestData = [
            'refreshToken' => 'invalid.token'
        ];

        $response = $this->apiRequest('POST', '/api/v1/auth/user/access', $refreshRequestData);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid refresh token', $responseData['error']);
    }

    public function testRefreshAccessTokenWithInvalidRole(): void
    {
        $passwordHasher = $this->client->getContainer()->get('security.user_password_hasher');
        
        $user = new User();
        $user->setEmail(self::TEST_USER_DATA['email']);
        $user->setPassword($passwordHasher->hashPassword($user, self::TEST_USER_DATA['password']));
        $user->setRole(UserRole::USER);
        $user->setFirstName(self::TEST_USER_DATA['firstName']);
        $user->setLastName(self::TEST_USER_DATA['lastName']);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $requestData = [
            'email' => self::TEST_USER_DATA['email'],
            'password' => self::TEST_USER_DATA['password'],
        ];

        $this->apiRequest('POST', '/api/v1/auth/user/login', $requestData);

        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);

        $refreshRequestData = [
            'refreshToken' => $loginResponse['refreshToken']
        ];

        $response = $this->apiRequest('POST', '/api/v1/auth/admin/access', $refreshRequestData);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid role', $responseData['error']);
    }
}
