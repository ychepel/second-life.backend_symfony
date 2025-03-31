<?php

namespace App\Tests\Controller;

use App\Entity\Image;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class UserRegistrationControllerTest extends ControllerTest
{

    private const TEST_IMAGES = [
        'd3f1a2b3-c456-789d-012e-3456789abcde',
        'a1b2c3d4-e5f6-7890-1234-56789abcdef0',
        '0fedcba9-8765-4321-0fed-cba987654321'
    ];

    private const TEST_USER_DATA = [
        'firstName' => 'John',
        'lastName' => 'Smith',
        'email' => 'user@mail.com',
        'password' => 'Qwerty!123'
    ];

    public function testRegisterUserSuccess(): void
    {
        $data = array_merge(
            ['baseNameOfImages' => self::TEST_IMAGES],
            self::TEST_USER_DATA
        );

        $response = $this->apiRequest('POST','/api/v1/users/register',$data);

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

        // Verify images
        $this->assertArrayHasKey('values', $responseData['images']);
        foreach (self::TEST_IMAGES as $imageId) {
            $this->assertArrayHasKey($imageId, $responseData['images']['values']);
            $this->assertArrayHasKey('1024x1024', $responseData['images']['values'][$imageId]);
            $this->assertArrayHasKey('320x320', $responseData['images']['values'][$imageId]);
            $this->assertArrayHasKey('64x64', $responseData['images']['values'][$imageId]);
        }

        // Verify user in database
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => self::TEST_USER_DATA['email']]);

        $this->assertNotNull($user);
        $this->assertEquals(self::TEST_USER_DATA['firstName'], $user->getFirstName());
        $this->assertEquals(self::TEST_USER_DATA['lastName'], $user->getLastName());
        $this->assertTrue($user->isActive());

        // Verify images in database
        foreach (self::TEST_IMAGES as $imageId) {
            $image = $this->entityManager->getRepository(Image::class)
                ->findOneBy(['baseName' => $imageId]);
            $this->assertNotNull($image);
            $this->assertEquals($user->getId(), $image->getEntityId());
            $this->assertEquals('user', $image->getEntityType());
        }
    }

    public function testRegisterUserWithInvalidEmail(): void
    {
        $data = array_merge(
            ['baseNameOfImages' => self::TEST_IMAGES],
            self::TEST_USER_DATA
        );
        $data['email'] = 'invalid-email';

        $response = $this->apiRequest('POST','/api/v1/users/register',$data);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertContains('email', array_column($responseData['errors'], 'field'));
    }

    public function testRegisterUserWithWeakPassword(): void
    {
        $data = array_merge(
            ['baseNameOfImages' => self::TEST_IMAGES],
            self::TEST_USER_DATA
        );
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
        $data = array_merge(
            ['baseNameOfImages' => self::TEST_IMAGES],
            self::TEST_USER_DATA
        );

        $response1 = $this->apiRequest('POST','/api/v1/users/register',$data);
        $this->assertEquals(Response::HTTP_CREATED, $response1->getStatusCode());

        // Second registration with same email
        $response2 = $this->apiRequest('POST','/api/v1/users/register',$data);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response2->getStatusCode());
    }
}
