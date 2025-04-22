<?php

namespace App\Service;

use App\Dto\CategoryResponseDto;
use App\Entity\Category;
use App\Mapper\CategoryMappingService;
use App\Repository\CategoryRepository;
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
        /** @var CategoryRepository $repository */
        $repository = $this->entityManager->getRepository(Category::class);
        $category = $repository->findOneWithImage(['id' => $id, 'isActive' => true]);

        return $category === null ? null : $this->mappingService->toDto($category);
    }

    public function getAll(bool $activeOnly = true): array
    {
        /** @var CategoryRepository $repository */
        $repository = $this->entityManager->getRepository(Category::class);
        $categories = $repository->getAllWithImages();

        if ($activeOnly) {
            $categories = array_filter($categories, function (Category $category) {
                return $category->isActive();
            });
        }

        return array_map(function (Category $category) {
            return $this->mappingService->toDto($category);
        }, $categories);
    }
}