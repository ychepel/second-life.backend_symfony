<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1')]
class CategoryController extends AbstractController
{
    #[Route('/categories/{id}', name: 'category_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getCategory(int $id, CategoryRepository $categoryRepository): JsonResponse
    {
        $category = $categoryRepository->findOneBy(['id' => $id, 'isActive' => true]);

        if (!$category) {
            return $this->json([
                'error' => 'Category not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $response = [
            'images' => [
                'values' => $this->formatImages($category->getImages())
            ],
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
            'active' => $category->isActive()
        ];

        return $this->json($response);
    }

    #[Route('/categories', name: 'categories_list', methods: ['GET'])]
    public function getCategories(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findBy(['isActive' => true]);

        $response = [
            'categories' => array_map(function (Category $category) {
                return [
                    'images' => [
                        'values' => $this->formatImages($category->getImages())
                    ],
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'description' => $category->getDescription(),
                    'active' => $category->isActive()
                ];
            }, $categories)
        ];

        return $this->json($response);
    }

    #[Route('/categories/get-all-for-admin', name: 'categories_get_all_for_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllCategoriesForAdmin(
        CategoryRepository $categoryRepository
    ): JsonResponse {
        try {
            $categories = $categoryRepository->findAll();

            $response = [
                'categories' => array_map(function (Category $category) {
                    return [
                        'images' => [
                            'values' => $this->formatImages($category->getImages())
                        ],
                        'id' => $category->getId(),
                        'name' => $category->getName(),
                        'description' => $category->getDescription(),
                        'active' => $category->isActive()
                    ];
                }, $categories)
            ];

            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function formatImages(?array $images): array
    {
        if (!$images) {
            return [];
        }

        $formattedImages = [];
        foreach ($images as $imageId => $imageData) {
            $formattedImages[$imageId] = [
                '1024x1024' => sprintf('https://domain.com/prod/offer/1/1024x1024_%s.jpg', $imageId),
                '320x320' => sprintf('https://domain.com/prod/offer/1/320x320_%s.jpg', $imageId),
                '64x64' => sprintf('https://domain.com/prod/offer/1/64x64_%s.jpg', $imageId)
            ];
        }

        return $formattedImages;
    }
}
