<?php

namespace App\Mapper;

use App\Dto\LocationDto;
use App\Entity\Location;

class LocationMappingService extends MappingEntityWithImage
{
    public function toDto(Location $location): LocationDto
    {
        return new LocationDto($location->getId(), $location->getName());
    }
}