<?php

namespace App\Controller;

use App\Entity\Location;
use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

        return new JsonResponse($response);
    }

    #[Route('/locations/{id}', name: 'location_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getLocation(int $id, LocationRepository $locationRepository): JsonResponse
    {
        $location = $locationRepository->find($id);

        if (!$location) {
            return new JsonResponse([
                'error' => 'Location not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $location->getId(),
            'name' => $location->getName()
        ]);
    }
}
