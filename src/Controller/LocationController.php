<?php

namespace App\Controller;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1')]
class LocationController extends AbstractController
{
    #[Route('/locations', name: 'locations_list', methods: ['GET'])]
    public function getLocations(LocationRepository $locationRepository): JsonResponse
    {
        $locations = $locationRepository->findAll();

        $response = [
            'locations' => array_map(function (Location $location) {
                return [
                    'id' => $location->getId(),
                    'name' => $location->getName()
                ];
            }, $locations)
        ];

        return $this->json($response);
    }

    #[Route('/locations/{id}', name: 'location_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getLocation(int $id, LocationRepository $locationRepository): JsonResponse
    {
        $location = $locationRepository->find($id);

        if (!$location) {
            return $this->json([
                'error' => 'Location not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $location->getId(),
            'name' => $location->getName()
        ]);
    }
}
