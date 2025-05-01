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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryMappingService $mappingService,
        private readonly ImageService $imageService,
    ) {
    }

    public function getActiveById(int $id): ?CategoryResponseDto
    {
        /** @var CategoryRepository $repository */
        $repository = $this->entityManager->getRepository(Category::class);
        $category = $repository->findOneWithImage(['id' => $id, 'isActive' => true]);

        return null === $category ? null : $this->mappingService->toDto($category);
    }

    public function getAll(bool $activeOnly = true): array
    {
        /** @var CategoryRepository $repository */
        $repository = $this->entityManager->getRepository(Category::class);
        $categories = $repository->getAllWithImages();

        if ($activeOnly) {
            $categories = array_filter($categories, fn(Category $category) => $category->isActive());
        }

        return array_map(fn(Category $category) => $this->mappingService->toDto($category), $categories);
    }

    /**
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

    /**
     * @throws NotFoundHttpException
     */
    public function updateCategory(int $id, CategoryRequestDto $request): CategoryResponseDto
    {
        /** @var CategoryRepository $repository */
        $repository = $this->entityManager->getRepository(Category::class);
        $category = $repository->findOneWithImage(['id' => $id]);

        if (null === $category) {
            throw new NotFoundHttpException('Category does not exist');
        }

        $category->setName($request->getName())
            ->setDescription($request->getDescription());

        if (!empty($request->getBaseNameOfImages())) {
            $images = $this->imageService->attachImages('category', $category->getId(), $request->getBaseNameOfImages());
            $category->setImages($images);
        }

        $this->entityManager->flush();

        return $this->mappingService->toDto($category);
    }

    /**
     * Soft delete category by id (set isActive=false).
     *
     * @throws NotFoundHttpException
     */
    public function softDeleteCategory(int $id): CategoryResponseDto
    {
        /** @var CategoryRepository $repository */
        $repository = $this->entityManager->getRepository(Category::class);
        $category = $repository->findOneWithImage(['id' => $id]);
        if (null === $category) {
            throw new NotFoundHttpException('Category does not exist');
        }
        $category->setIsActive(false);
        $this->entityManager->flush();

        return $this->mappingService->toDto($category);
    }

    /**
     * Activate category by id (set isActive=true).
     *
     * @throws NotFoundHttpException
     */
    public function activateCategory(int $id): CategoryResponseDto
    {
        /** @var CategoryRepository $repository */
        $repository = $this->entityManager->getRepository(Category::class);
        $category = $repository->findOneWithImage(['id' => $id]);
        if (null === $category) {
            throw new NotFoundHttpException('Category does not exist');
        }
        $category->setIsActive(true);
        $this->entityManager->flush();

        return $this->mappingService->toDto($category);
    }
}
