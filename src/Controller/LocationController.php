<?php

namespace App\Controller;

use App\Service\LocationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/locations')]
class LocationController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly LocationService $locationService
    ) { }

    #[Route('', name: 'locations_list', methods: ['GET'])]
    public function getLocations(): JsonResponse
    {
        try {
            $locations = $this->locationService->getAll();

            return $this->json(['locations' => $locations]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting locations: ' . $e->getMessage());

            return $this->json([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'location_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getLocation(int $id): JsonResponse
    {
        try {
            $locationDto = $this->locationService->getById($id);
        } catch (\Exception $e) {
            $this->logger->error('Error getting location: ' . $e->getMessage());

            return $this->json([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($locationDto === null) {
            return $this->json(['error' => 'Location not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($locationDto);
    }
}
