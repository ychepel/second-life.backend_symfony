<?php

namespace App\Tests\Controller;

use App\Entity\Location;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\HttpFoundation\Response;

class LocationControllerTest extends ControllerTest
{
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

        $locations = [
            ['id' => 1, 'name' => 'Baden-Württemberg'],
            ['id' => 2, 'name' => 'Bayern'],
            ['id' => 3, 'name' => 'Berlin'],
        ];

        foreach ($locations as $locationData) {
            $location = new Location();
            $location->setId($locationData['id']);
            $location->setName($locationData['name']);
            $this->entityManager->persist($location);
        }

        $this->entityManager->flush();
    }

    public function testGetLocationsSuccess(): void
    {
        $response = $this->apiRequest('GET', '/api/v1/locations');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('locations', $responseData);
        $this->assertIsArray($responseData['locations']);

        foreach ($responseData['locations'] as $location) {
            $this->assertArrayHasKey('id', $location);
            $this->assertArrayHasKey('name', $location);
            $this->assertIsInt($location['id']);
            $this->assertIsString($location['name']);
        }
    }

    public function testGetLocationSuccess(): void
    {
        $response = $this->apiRequest('GET', '/api/v1/locations/2');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);
        $this->assertEquals(2, $responseData['id']);
        $this->assertEquals('Bayern', $responseData['name']);
    }

    public function testGetLocationNotFound(): void
    {
        $response = $this->apiRequest('GET', '/api/v1/locations/999');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Location not found', $responseData['error']);
    }

    public function testGetLocationsInvalidId(): void
    {
        $response = $this->apiRequest('GET', '/api/v1/locations/abc');
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }
}
