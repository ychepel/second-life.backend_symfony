<?php

namespace App\Controller;

use App\Dto\CategoryRequestDto;
use App\Exception\DuplicateException;
use App\Service\CategoryService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CategoryController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CategoryService $categoryService,
    ) {
    }
    #[Route('/api/v1/categories/{id}', name: 'category_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getCategory(int $id): JsonResponse
    {
        try {
            $categoryDto = $this->categoryService->getActiveById($id);
        } catch (\Exception $e) {
            $this->logger->error('Error getting category: '.$e->getMessage());

            return $this->json([
                'error' => 'Internal server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (null === $categoryDto) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($categoryDto);
    }
    #[Route('/api/v1/categories', name: 'categories_list', methods: ['GET'])]
    public function getCategories(): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAll();

            return $this->json(['categories' => $categories]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting categories: '.$e->getMessage());

            return $this->json([
                'error' => 'Internal server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/api/v1/categories/get-all-for-admin', name: 'categories_get_all_for_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllCategoriesForAdmin(): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAll(false);

            return $this->json(['categories' => $categories]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting categories: '.$e->getMessage());

            return $this->json([
                'error' => 'Internal server error',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route(path: '/api/v1/categories', name: 'category_add', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function register(#[MapRequestPayload] CategoryRequestDto $request): JsonResponse
    {
        try {
            $response = $this->categoryService->createCategory($request);
        } catch (DuplicateException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return $this->json($response, Response::HTTP_CREATED);
    }
    #[Route(path: '/api/v1/categories/{id}', name: 'category_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateCategory(int $id, #[MapRequestPayload] CategoryRequestDto $request): JsonResponse
    {
        try {
            $categoryDto = $this->categoryService->updateCategory($id, $request);

            return $this->json($categoryDto, Response::HTTP_OK);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
    #[Route(path: '/api/v1/categories/{id}', name: 'category_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteCategory(int $id): JsonResponse
    {
        try {
            $categoryDto = $this->categoryService->softDeleteCategory($id);

            return $this->json($categoryDto, Response::HTTP_OK);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
    #[Route(path: '/api/v1/categories/{id}/set-active', name: 'category_set_active', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function setCategoryActive(int $id): JsonResponse
    {
        try {
            $categoryDto = $this->categoryService->activateCategory($id);

            return $this->json($categoryDto, Response::HTTP_OK);
        } catch (NotFoundHttpException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
