<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\AccessException;
use App\Exception\ServiceException;
use App\Repository\ImageRepository;
use App\Repository\OfferRepository;
use App\Service\ImageService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1')]
class ImageController extends AbstractController
{
    public function __construct(
        private readonly ImageService $imageService,
        private readonly Security $security,
        private readonly ImageRepository $imageRepository,
        private readonly OfferRepository $offerRepository
    ) { }

    #[Route('/images', name: 'image_upload', methods: ['POST'])]
    public function uploadImage(Request $request): JsonResponse {
        try {
            /** @var User $user */
            $user = $this->security->getUser();
            $entityType = $request->request->get('entityType');
            $entityId = (int) $request->request->get('entityId');
            $file = $request->files->get('file');
            $result = $this->imageService->uploadImage(
                $file,
                $entityType,
                $entityId,
                $user,
                $this->imageRepository,
                $this->offerRepository
            );
            $response = ['values' => [$result['baseName'] => $result['imagePaths']]];
            return $this->json($response);
        } catch (AccessException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (ServiceException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/images', name: 'image_delete', methods: ['DELETE'])]
    public function deleteImage(Request $request): JsonResponse {
        try {
            /** @var User $user */
            $user = $this->security->getUser();
            $requestData = json_decode($request->getContent(), true);
            $baseName = $requestData['baseName'] ?? null;
            if (!$baseName) {
                return $this->json(['error' => 'Base name is required'], Response::HTTP_BAD_REQUEST);
            }
            $this->imageService->deleteImage(
                $baseName,
                $user,
                $this->imageRepository,
                $this->offerRepository
            );
            return $this->json(['message' => 'Image deleted successfully']);
        } catch (AccessException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (ServiceException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
