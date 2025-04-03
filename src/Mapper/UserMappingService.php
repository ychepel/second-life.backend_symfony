<?php

namespace App\Mapper;

use App\Dto\UserResponseDto;
use App\Entity\User;

class UserMappingService extends MappingEntityWithImage
{
    public function toDto(User $user): UserResponseDto
    {
        $response = new UserResponseDto();
        $response->setId($user->getId())
            ->setFirstName($user->getFirstName())
            ->setLastName($user->getLastName())
            ->setEmail($user->getEmail())
            ->setCreatedAt($user->getCreatedAt())
            ->setLocationId($user->getLocation()?->getId())
            ->setLastActive($user->getUpdatedAt()) //TODO: implement logic for storing login dates
            ->setImages(parent::getImages($user));

        return $response;
    }
}