<?php

namespace App\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

abstract class ControllerTest extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = self::getContainer()->get('doctrine')->getManager();

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        unset($this->entityManager);
    }

    protected function apiRequest(string $method, string $url, array $data = [], array $files = [], string $accessToken = ''): Response
    {
        $server = ['HTTP_ACCEPT' => 'application/json'];
        if ('' !== $accessToken) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$accessToken;
        }
        $content = null;

        if (empty($files)) {
            $server['CONTENT_TYPE'] = 'application/json';
            $content = json_encode($data);
            $data = [];
        } else {
            $server['CONTENT_TYPE'] = 'multipart/form-data';
        }

        $this->client->request(
            $method,
            $url,
            $data,
            $files,
            $server,
            $content
        );

        return $this->client->getResponse();
    }
}
