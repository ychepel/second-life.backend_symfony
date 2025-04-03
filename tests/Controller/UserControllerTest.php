<?php

namespace App\Tests\Controller;

use App\Entity\Image;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends ControllerTest
{
    private const TEST_USER_DATA = [
        'firstName' => 'John',
        'lastName' => 'Smith',
        'email' => 'user@mail.com',
        'password' => 'Qwerty!123'
    ];

    public function testRegisterUserSuccess(): void
    {
        $response = $this->apiRequest('POST','/api/v1/users/register',self::TEST_USER_DATA);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('firstName', $responseData);
        $this->assertArrayHasKey('lastName', $responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('createdAt', $responseData);
        $this->assertArrayHasKey('locationId', $responseData);
        $this->assertArrayHasKey('lastActive', $responseData);
        $this->assertArrayHasKey('images', $responseData);

        // Verify user in database
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => self::TEST_USER_DATA['email']]);

        $this->assertNotNull($user);
        $this->assertEquals(self::TEST_USER_DATA['firstName'], $user->getFirstName());
        $this->assertEquals(self::TEST_USER_DATA['lastName'], $user->getLastName());
        $this->assertTrue($user->isActive());
    }

    public function testRegisterUserWithInvalidEmail(): void
    {
        $data = self::TEST_USER_DATA;
        $data['email'] = 'invalid-email';

        $response = $this->apiRequest('POST','/api/v1/users/register',$data);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertContains('email', array_column($responseData['errors'], 'field'));
    }

    public function testRegisterUserWithWeakPassword(): void
    {
        $data = self::TEST_USER_DATA;
        $data['password'] = 'weak';

        $response = $this->apiRequest('POST','/api/v1/users/register',$data);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertContains('password', array_column($responseData['errors'], 'field'));
    }

    public function testRegisterUserWithExistingEmail(): void
    {
        // First registration
        $response1 = $this->apiRequest('POST','/api/v1/users/register', self::TEST_USER_DATA);
        $this->assertEquals(Response::HTTP_CREATED, $response1->getStatusCode());

        // Second registration with same email
        $response2 = $this->apiRequest('POST','/api/v1/users/register', self::TEST_USER_DATA);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response2->getStatusCode());
        $responseData = json_decode($response2->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertContains('email', array_column($responseData['errors'], 'field'));
    }
}
