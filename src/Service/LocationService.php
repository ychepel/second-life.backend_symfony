<?php

namespace App\Service;

use App\Dto\LocationDto;
use App\Entity\Location;
use App\Mapper\LocationMappingService;
use App\Repository\LocationRepository;

class LocationService
{
    public function __construct(
        private readonly LocationRepository     $locationRepository,
        private readonly LocationMappingService $mappingService
    ) { }

    public function getAll(): array
    {
        $locations = $this->locationRepository->findAll();

        return array_map(function (Location $location) {
            return $this->mappingService->toDto($location);
        }, $locations);
    }

    public function getById(int $id): ?LocationDto
    {
        $location = $this->locationRepository->find($id);

        return $location !== null ? $this->mappingService->toDto($location) : null;
    }
}