<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Offer;
use App\Entity\User;
use App\Enum\OfferStatus;
use App\Enum\UserRole;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class ImageControllerTest extends ControllerTest
{
    private const TEST_USER1_DATA = [
        'email' => 'user1@example.com',
        'password' => 'Qwerty!123',
        'firstName' => 'Test1',
        'lastName' => 'User1'
    ];

    private const TEST_USER2_DATA = [
        'email' => 'user2@example.com',
        'password' => 'Qwerty!123',
        'firstName' => 'Test2',
        'lastName' => 'User2'
    ];

    private const TEST_ADMIN_DATA = [
        'email' => 'admin@example.com',
        'password' => 'Qwerty!123',
        'firstName' => 'Admin',
        'lastName' => 'User'
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->removeTestImages();
        parent::tearDown();
    }

    private function setUpDatabase(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $passwordHasher = $this->client->getContainer()->get('security.user_password_hasher');
        
        $user1 = new User();
        $user1->setEmail(self::TEST_USER1_DATA['email']);
        $user1->setPassword($passwordHasher->hashPassword($user1, self::TEST_USER1_DATA['password']));
        $user1->setRole(UserRole::ROLE_USER);
        $user1->setFirstName(self::TEST_USER1_DATA['firstName']);
        $user1->setLastName(self::TEST_USER1_DATA['lastName']);

        $user2 = new User();
        $user2->setEmail(self::TEST_USER2_DATA['email']);
        $user2->setPassword($passwordHasher->hashPassword($user1, self::TEST_USER2_DATA['password']));
        $user2->setRole(UserRole::ROLE_USER);
        $user2->setFirstName(self::TEST_USER2_DATA['firstName']);
        $user2->setLastName(self::TEST_USER2_DATA['lastName']);

        $admin = new User();
        $admin->setEmail(self::TEST_ADMIN_DATA['email']);
        $admin->setPassword($passwordHasher->hashPassword($admin, self::TEST_ADMIN_DATA['password']));
        $admin->setRole(UserRole::ROLE_ADMIN);
        $admin->setFirstName(self::TEST_ADMIN_DATA['firstName']);
        $admin->setLastName(self::TEST_ADMIN_DATA['lastName']);

        $category = new Category();
        $category->setName('Basic category');
        $category->setIsActive(true);
        
        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->persist($admin);
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $offer = new Offer();
        $offer->setTitle('Test Offer');
        $offer->setDescription('Test Description');
        $offer->setAuctionDurationDays(3);
        $offer->setStatus(OfferStatus::DRAFT);
        $offer->setCategory($category);
        $offer->setUser($user1);

        $this->entityManager->persist($offer);
        $this->entityManager->flush();
    }

    private function createTestFile(): UploadedFile
    {
        $file = new \SplFileInfo(__DIR__ . '/fixtures/test.jpg');
        return new UploadedFile(
            $file->getPathname(),
            'test.jpg',
            'image/jpeg',
            UPLOAD_ERR_OK,
            true
        );
    }

    public function testUploadImageSuccess(): void
    {
        $loginRequest = [
            'email' => self::TEST_USER1_DATA['email'],
            'password' => self::TEST_USER1_DATA['password']
        ];

        $loginResponse = $this->apiRequest('POST', '/api/v1/auth/user/login', $loginRequest);
        $loginData = json_decode($loginResponse->getContent(), true);
        $accessToken = $loginData['accessToken'];

        $offer = $this->entityManager->getRepository(Offer::class)->findOneBy([
            'user' => $this->entityManager->getRepository(User::class)->findOneBy([
                'email' => self::TEST_USER1_DATA['email']
            ])
        ]);

        $file = $this->createTestFile();
        $response = $this->apiRequest('POST', '/api/v1/images', [
            'entityType' => 'offer',
            'entityId' => $offer->getId()
        ], ['file' => $file], accessToken: $accessToken);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('values', $responseData);
        $this->assertIsArray($responseData['values']);

        $imageData = reset($responseData['values']);

        $this->assertArrayHasKey('1024x1024', $imageData);
        $this->assertArrayHasKey('320x320', $imageData);
        $this->assertArrayHasKey('64x64', $imageData);

        $relativePath1024 = parse_url($imageData['1024x1024'], PHP_URL_PATH);
        $filePath1024 = __DIR__ . '/../../public' . $relativePath1024;
        $this->assertFileExists($filePath1024);
        $this->assertNotFalse(imagecreatefromstring(file_get_contents($filePath1024)));

        $relativePath320 = parse_url($imageData['320x320'], PHP_URL_PATH);
        $filePath320 = __DIR__ . '/../../public' . $relativePath320;
        $this->assertFileExists($filePath320);
        $this->assertNotFalse(imagecreatefromstring(file_get_contents($filePath320)));

        $relativePath64 = parse_url($imageData['64x64'], PHP_URL_PATH);
        $filePath64 = __DIR__ . '/../../public' . $relativePath64;
        $this->assertFileExists($filePath64);
        $this->assertNotFalse(imagecreatefromstring(file_get_contents($filePath64)));
    }

    public function testUploadImageInvalidEntityType(): void
    {
        $loginRequest = [
            'email' => self::TEST_USER1_DATA['email'],
            'password' => self::TEST_USER1_DATA['password']
        ];

        $loginResponse = $this->apiRequest('POST', '/api/v1/auth/user/login', $loginRequest);
        $loginData = json_decode($loginResponse->getContent(), true);
        $accessToken = $loginData['accessToken'];

        $file = $this->createTestFile();
        $response = $this->apiRequest('POST', '/api/v1/images', [
            'entityType' => 'invalid',
            'entityId' => 1
        ], ['file' => $file], accessToken: $accessToken);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Invalid entity type', $responseData['error']);
    }

    public function testUploadImageFileSizeExceeded(): void
    {
        $loginRequest = [
            'email' => self::TEST_USER1_DATA['email'],
            'password' => self::TEST_USER1_DATA['password']
        ];

        $loginResponse = $this->apiRequest('POST', '/api/v1/auth/user/login', $loginRequest);
        $loginData = json_decode($loginResponse->getContent(), true);
        $accessToken = $loginData['accessToken'];

        $file = new \SplFileInfo(__DIR__ . '/fixtures/large.jpg');
        $file = new UploadedFile(
            $file->getPathname(),
            'large.jpg',
            'image/jpeg',
            9 * 1024 * 1024
        );

        $response = $this->apiRequest('POST', '/api/v1/images', [
            'entityType' => 'offer',
            'entityId' => null
        ], ['file' => $file], accessToken: $accessToken);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testUploadImageMaxImagesReached(): void
    {
        $loginRequest = [
            'email' => self::TEST_USER1_DATA['email'],
            'password' => self::TEST_USER1_DATA['password']
        ];

        $loginResponse = $this->apiRequest('POST', '/api/v1/auth/user/login', $loginRequest);
        $loginData = json_decode($loginResponse->getContent(), true);
        $accessToken = $loginData['accessToken'];

        $offer = $this->entityManager->getRepository(Offer::class)->findOneBy([
            'user' => $this->entityManager->getRepository(User::class)->findOneBy([
                'email' => self::TEST_USER1_DATA['email']
            ])
        ]);

        for ($i = 0; $i < 5; $i++) {
            $file = $this->createTestFile();
            $this->apiRequest('POST', '/api/v1/images', [
                'entityType' => 'offer',
                'entityId' => $offer->getId()
            ], ['file' => $file], accessToken: $accessToken);
        }

        $file = $this->createTestFile();
        $response = $this->apiRequest('POST', '/api/v1/images', [
            'entityType' => 'offer',
            'entityId' => $offer->getId()
        ], ['file' => $file], accessToken: $accessToken);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Maximum number of images reached', $responseData['error']);
    }

    public function testUploadImageAccessDenied(): void
    {
        $loginRequest = [
            'email' => self::TEST_USER1_DATA['email'],
            'password' => self::TEST_USER1_DATA['password']
        ];

        $loginResponse = $this->apiRequest('POST', '/api/v1/auth/user/login', $loginRequest);
        $loginData = json_decode($loginResponse->getContent(), true);
        $accessToken = $loginData['accessToken'];

        $file = $this->createTestFile();
        $response = $this->apiRequest('POST', '/api/v1/images', [
            'entityType' => 'category',
            'entityId' => 1
        ], ['file' => $file], accessToken: $accessToken);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testDeleteImageSuccess(): void
    {
        $loginRequest = [
            'email' => self::TEST_USER1_DATA['email'],
            'password' => self::TEST_USER1_DATA['password']
        ];

        $loginResponse = $this->apiRequest('POST', '/api/v1/auth/user/login', $loginRequest);
        $loginData = json_decode($loginResponse->getContent(), true);
        $accessToken = $loginData['accessToken'];

        $offer = $this->entityManager->getRepository(Offer::class)->findOneBy([
            'user' => $this->entityManager->getRepository(User::class)->findOneBy([
                'email' => self::TEST_USER1_DATA['email']
            ])
        ]);

        $file = $this->createTestFile();

        $response = $this->apiRequest('POST', '/api/v1/images', [
            'entityType' => 'offer',
            'entityId' => $offer->getId(),
        ], ['file' => $file], accessToken: $accessToken);

        $responseData = json_decode($response->getContent(), true);
        $baseName = key($responseData['values']);

        $response = $this->apiRequest('DELETE', '/api/v1/images', [
            'baseName' => $baseName
        ], accessToken: $accessToken);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testDeleteImageAccessDenied(): void
    {
        $loginRequest1 = [
            'email' => self::TEST_USER1_DATA['email'],
            'password' => self::TEST_USER1_DATA['password']
        ];

        $loginResponse1 = $this->apiRequest('POST', '/api/v1/auth/user/login', $loginRequest1);
        $loginData1 = json_decode($loginResponse1->getContent(), true);
        $accessToken1 = $loginData1['accessToken'];

        $user1 = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => self::TEST_USER1_DATA['email']
        ]);
        $offer = $this->entityManager->getRepository(Offer::class)->findOneBy([
            'user' => $user1
        ]);

        $file = $this->createTestFile();
        $response = $this->apiRequest('POST', '/api/v1/images', [
            'entityType' => 'offer',
            'entityId' => $offer->getId()
        ], ['file' => $file], accessToken: $accessToken1);

        $responseData = json_decode($response->getContent(), true);
        $baseName = key($responseData['values']);

        $loginRequest2 = [
            'email' => self::TEST_USER2_DATA['email'],
            'password' => self::TEST_USER2_DATA['password']
        ];

        $loginResponse2 = $this->apiRequest('POST', '/api/v1/auth/user/login', $loginRequest2);
        $loginData2 = json_decode($loginResponse2->getContent(), true);
        $accessToken2 = $loginData2['accessToken'];

        $response = $this->apiRequest('DELETE', '/api/v1/images', [
            'baseName' => $baseName
        ], accessToken: $accessToken2);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Access denied', $responseData['error']);
    }

    private function removeTestImages(): void
    {
        $directory = __DIR__ . '/../../public/test-images';

        if (!is_dir($directory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
