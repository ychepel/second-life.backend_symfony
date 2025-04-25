<?php

namespace App\Service;

use App\Dto\CategoryRequestDto;
use App\Dto\CategoryResponseDto;
use App\Entity\Category;
use App\Exception\DuplicateException;
use App\Mapper\CategoryMappingService;
use App\Repository\CategoryRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class CategoryService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryMappingService $mappingService,
        private readonly ImageService $imageService,
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

    /**
     * @param CategoryRequestDto $request
     * @return CategoryResponseDto
     * @throws DuplicateException
     */
    public function createCategory(CategoryRequestDto $request): CategoryResponseDto
    {
        try {
            $category = $this->entityManager->wrapInTransaction(function (EntityManagerInterface $em) use ($request) {
                $newCategory = new Category();
                $newCategory->setName($request->getName())
                    ->setDescription($request->getDescription())
                    ->setIsActive(true);
                $em->persist($newCategory);

                if (!empty($request->getBaseNameOfImages())) {
                    $em->flush();
                    $images = $this->imageService->attachImages('category', $newCategory->getId(), $request->getBaseNameOfImages());
                    $newCategory->setImages($images);
                }

                return $newCategory;
            });
        } catch (UniqueConstraintViolationException) {
            throw new DuplicateException('Category with this name already exists');
        }

        return $this->mappingService->toDto($category);
    }
}