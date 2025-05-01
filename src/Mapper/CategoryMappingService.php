<?php

namespace App\Mapper;

use App\Dto\CategoryResponseDto;
use App\Entity\Category;

class CategoryMappingService extends MappingEntityWithImage
{
    public function toDto(Category $category): CategoryResponseDto
    {
        $response = new CategoryResponseDto();
        $response->setId($category->getId());
        $response->setName($category->getName());
        $response->setDescription($category->getDescription());
        $response->setActive($category->isActive());
        $response->setImages(parent::getImages($category));

        return $response;
    }
}
