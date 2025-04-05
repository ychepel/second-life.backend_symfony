<?php

namespace App\Controller;

use App\Service\CategoryService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1')]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CategoryService $categoryService
    ) { }

    #[Route('/categories/{id}', name: 'category_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getCategory(int $id): JsonResponse
    {
        try {
            $categoryDto = $this->categoryService->getActiveById($id);
        } catch (\Exception $e) {
            $this->logger->error('Error getting category: ' . $e->getMessage());

            return $this->json([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($categoryDto === null) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($categoryDto);
    }

    #[Route('/categories', name: 'categories_list', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAll();

            return $this->json(['categories' => $categories]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting categories: ' . $e->getMessage());

            return $this->json([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/categories/get-all-for-admin', name: 'categories_get_all_for_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllCategoriesForAdmin(): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAll(false);

            return $this->json(['categories' => $categories]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting categories: ' . $e->getMessage());

            return $this->json([
                'error' => 'Internal server error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
