<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;

class RejectionReasonControllerTest extends ControllerTest
{
    private const TEST_ADMIN_DATA = [
        'email' => 'admin@example.com',
        'password' => 'Qwerty!123',
        'firstName' => 'Admin',
        'lastName' => 'User',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    private function setUpDatabase(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        // Create admin user
        $passwordHasher = $this->client->getContainer()->get('security.user_password_hasher');

        $admin = new User();
        $admin->setEmail(self::TEST_ADMIN_DATA['email']);
        $admin->setPassword($passwordHasher->hashPassword($admin, self::TEST_ADMIN_DATA['password']));
        $admin->setRole(UserRole::ROLE_ADMIN);
        $admin->setFirstName(self::TEST_ADMIN_DATA['firstName']);
        $admin->setLastName(self::TEST_ADMIN_DATA['lastName']);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();
    }

    public function testGetRejectionReasonsSuccess(): void
    {
        // Login as admin to get access token
        $loginRequest = [
            'email' => self::TEST_ADMIN_DATA['email'],
            'password' => self::TEST_ADMIN_DATA['password'],
        ];

        $loginResponse = $this->apiRequest('POST', '/api/v1/auth/admin/login', $loginRequest);
        $loginData = json_decode($loginResponse->getContent(), true);
        $accessToken = $loginData['accessToken'];

        // Get rejection reasons
        $response = $this->apiRequest('GET', '/api/v1/rejection-reasons', accessToken: $accessToken);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('reasons', $responseData);
        $this->assertIsArray($responseData['reasons']);

        foreach ($responseData['reasons'] as $reason) {
            $this->assertArrayHasKey('id', $reason);
            $this->assertArrayHasKey('name', $reason);
            $this->assertIsInt($reason['id']);
            $this->assertIsString($reason['name']);
        }
    }

    public function testGetRejectionReasonsWithoutToken(): void
    {
        $response = $this->apiRequest('GET', '/api/v1/rejection-reasons');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testGetRejectionReasonsWithInvalidToken(): void
    {
        // Login as admin to get access token
        $loginRequest = [
            'email' => self::TEST_ADMIN_DATA['email'],
            'password' => self::TEST_ADMIN_DATA['password'],
        ];

        $loginResponse = $this->apiRequest('POST', '/api/v1/auth/admin/login', $loginRequest);
        $loginData = json_decode($loginResponse->getContent(), true);

        // Clear cookies to invalidate the token
        $this->client->getCookieJar()->clear();

        // Try to get rejection reasons
        $response = $this->apiRequest('GET', '/api/v1/rejection-reasons');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testGetRejectionReasonsWithNonAdminUser(): void
    {
        // Create a regular user
        $passwordHasher = $this->client->getContainer()->get('security.user_password_hasher');

        $user = new User();
        $user->setEmail('user@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'Qwerty!123'));
        $user->setRole(UserRole::ROLE_USER);
        $user->setFirstName('Regular');
        $user->setLastName('User');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Login as regular user
        $loginRequest = [
            'email' => 'user@example.com',
            'password' => 'Qwerty!123',
        ];

        $loginResponse = $this->apiRequest('POST', '/api/v1/auth/user/login', $loginRequest);
        $loginData = json_decode($loginResponse->getContent(), true);
        $accessToken = $loginData['accessToken'];

        // Try to get rejection reasons
        $response = $this->apiRequest('GET', '/api/v1/rejection-reasons', accessToken: $accessToken);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
