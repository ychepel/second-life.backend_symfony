<?php

namespace App\Service;

use App\Dto\CategoryResponseDto;
use App\Entity\Category;
use App\Mapper\CategoryMappingService;
use Doctrine\ORM\EntityManagerInterface;

class CategoryService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryMappingService $mappingService
    )
    {}

    public function getActiveById(int $id): ?CategoryResponseDto
    {
        $repository = $this->entityManager->getRepository(Category::class);
        $category = $repository->findOneBy(['id' => $id, 'isActive' => true]);

        return $category === null ? null : $this->mappingService->toDto($category);
    }

    public function getAll(bool $activeOnly = true): array
    {
        $repository = $this->entityManager->getRepository(Category::class);
        $categories = $activeOnly
            ? $repository->findBy(['isActive' => true])
            : $repository->findAll();

        return array_map(function (Category $category) {
            return $this->mappingService->toDto($category);
        }, $categories);
    }
}