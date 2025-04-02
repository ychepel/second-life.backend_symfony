<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;

class CategoryControllerTest extends ControllerTest
{
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

        // Create test categories
        $categories = [
            [
                'name' => 'Electronics and gadgets',
                'description' => 'Smartphones,Laptops,Televisions,Peripherals',
                'active' => true,
                'images' => [
                    '47424034-00e8-4358-b352-e16023279883' => [
                        '1024x1024' => 'https://domain.com/prod/offer/1/1024x1024_47424034-00e8-4358-b352-e16023279883.jpg',
                        '320x320' => 'https://domain.com/prod/offer/1/320x320_47424034-00e8-4358-b352-e16023279883.jpg',
                        '64x64' => 'https://domain.com/prod/offer/1/64x64_47424034-00e8-4358-b352-e16023279883.jpg'
                    ],
                    'a1b2c3d4-e5f6-7890-1234-56789abcdef0' => [
                        '1024x1024' => 'https://domain.com/prod/offer/1/1024x1024_a1b2c3d4-e5f6-7890-1234-56789abcdef0.jpg',
                        '320x320' => 'https://domain.com/prod/offer/1/320x320_a1b2c3d4-e5f6-7890-1234-56789abcdef0.jpg',
                        '64x64' => 'https://domain.com/prod/offer/1/64x64_a1b2c3d4-e5f6-7890-1234-56789abcdef0.jpg'
                    ]
                ]
            ],
            [
                'name' => 'Furniture and Home Decor',
                'description' => 'Sofas,Tables and Chairs,Cabinets and Shelves,Decor and Accessories',
                'active' => true,
                'images' => []
            ],
            [
                'name' => 'Inactive Category',
                'description' => 'This category is inactive',
                'active' => false,
                'images' => []
            ]
        ];

        foreach ($categories as $categoryData) {
            $category = new Category();
            $category->setName($categoryData['name']);
            $category->setDescription($categoryData['description']);
            $category->setIsActive($categoryData['active']);
            $category->setImages($categoryData['images']);
            $this->entityManager->persist($category);
        }

        $this->entityManager->flush();
    }

    public function testGetCategorySuccess(): void
    {
        $response = $this->apiRequest('GET', '/api/v1/categories/1');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('images', $responseData);
        $this->assertArrayHasKey('values', $responseData['images']);
        //TODO: enable assertion after images logic implementation
//        $this->assertArrayHasKey('47424034-00e8-4358-b352-e16023279883', $responseData['images']['values']);
        $this->assertEquals('Electronics and gadgets', $responseData['name']);
        $this->assertEquals('Smartphones,Laptops,Televisions,Peripherals', $responseData['description']);
        $this->assertTrue($responseData['active']);
    }

    public function testGetCategoryNotFound(): void
    {
        $response = $this->apiRequest('GET', '/api/v1/categories/999');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Category not found', $responseData['error']);
    }

    public function testGetCategoryInactive(): void
    {
        $response = $this->apiRequest('GET', '/api/v1/categories/3');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Category not found', $responseData['error']);
    }

    public function testGetCategoriesSuccess(): void
    {
        $response = $this->apiRequest('GET', '/api/v1/categories');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('categories', $responseData);
        $this->assertIsArray($responseData['categories']);
        
        $categories = $responseData['categories'];
        $this->assertCount(2, $categories); // Only active categories
        
        foreach ($categories as $category) {
            $this->assertArrayHasKey('images', $category);
            $this->assertArrayHasKey('values', $category['images']);
            $this->assertArrayHasKey('id', $category);
            $this->assertArrayHasKey('name', $category);
            $this->assertArrayHasKey('description', $category);
            $this->assertArrayHasKey('active', $category);
            $this->assertTrue($category['active']);
        }
    }

    public function testGetAllCategoriesForAdminSuccess(): void
    {
        // Login as admin to get access token
        $loginRequest = [
            'email' => self::TEST_ADMIN_DATA['email'],
            'password' => self::TEST_ADMIN_DATA['password']
        ];

        $loginResponse = $this->apiRequest('POST', '/api/v1/auth/admin/login', $loginRequest);
        $loginData = json_decode($loginResponse->getContent(), true);
        $accessToken = $loginData['accessToken'];

        // Get all categories
        $response = $this->apiRequest('GET', '/api/v1/categories/get-all-for-admin', accessToken: $accessToken);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('categories', $responseData);
        $this->assertIsArray($responseData['categories']);
        
        $categories = $responseData['categories'];
        $this->assertCount(3, $categories); // All categories, including inactive
        
        foreach ($categories as $category) {
            $this->assertArrayHasKey('images', $category);
            $this->assertArrayHasKey('values', $category['images']);
            $this->assertArrayHasKey('id', $category);
            $this->assertArrayHasKey('name', $category);
            $this->assertArrayHasKey('description', $category);
            $this->assertArrayHasKey('active', $category);
        }
    }

    public function testGetAllCategoriesForAdminWithoutToken(): void
    {
        $response = $this->apiRequest('GET', '/api/v1/categories/get-all-for-admin');
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('JWT Token not found', $responseData['message']);
    }

    public function testGetAllCategoriesForAdminWithNonAdminUser(): void
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
            'password' => 'Qwerty!123'
        ];

        $loginResponse = $this->apiRequest('POST', '/api/v1/auth/user/login', $loginRequest);
        $loginData = json_decode($loginResponse->getContent(), true);
        $accessToken = $loginData['accessToken'];

        // Try to get all categories
        $response = $this->apiRequest('GET', '/api/v1/categories/get-all-for-admin', accessToken: $accessToken);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }
}
