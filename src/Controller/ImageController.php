<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\ImageRepository;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Model\Binary;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/v1')]
class ImageController extends AbstractController
{
    private const MAX_FILE_SIZE = 8388608;
    private const MAX_OFFER_IMAGES = 5;
    private const MAX_USER_IMAGES = 1;
    private const MAX_CATEGORY_IMAGES = 1;
    private const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security
    ) { }

    #[Route('/images', name: 'image_upload', methods: ['POST'])]
    public function uploadImage(
        Request $request,
        ImageRepository $imageRepository,
        OfferRepository $offerRepository,
        TranslatorInterface $translator,
        ValidatorInterface $validator,
        FilterManager $filterManager
    ): JsonResponse {
        try {
            /** @var User $user */
            $user = $this->security->getUser();

            // Get request data
            $entityType = $request->request->get('entityType');
            $entityId = $request->request->get('entityId');
            $file = $request->files->get('file');;

            // Validate input
            if (!$entityType || !in_array($entityType, ['offer', 'user', 'category'])) {
                return $this->json([
                    'error' => $translator->trans('Invalid entity type')
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!$file) {
                return $this->json([
                    'error' => $translator->trans('File is required')
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate file
            $constraints = new File([
                'maxSize' => self::MAX_FILE_SIZE,
                'mimeTypes' => self::ALLOWED_IMAGE_TYPES,
                'mimeTypesMessage' => $translator->trans('Please upload a valid image file (JPEG or PNG)')
            ]);

            $errors = $validator->validate($file, $constraints);
            if (count($errors) > 0) {
                return $this->json([
                    'error' => $errors[0]->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }

            // Check permissions and image limits
            if ($entityId !== null) {
                if ($entityType === 'offer') {
                    $offer = $offerRepository->findOneBy(['id' => $entityId, 'user' => $user]);
                    if (!$offer) {
                        return $this->json([
                            'error' => $translator->trans('Access denied')
                        ], Response::HTTP_FORBIDDEN);
                    }

                    $offerImages = $imageRepository->findBy(['entityType' => 'offer', 'entityId' => $entityId]);
                    if (count($offerImages) >= self::MAX_OFFER_IMAGES) {
                        return $this->json([
                            'error' => $translator->trans('Maximum number of images reached')
                        ], Response::HTTP_BAD_REQUEST);
                    }
                } elseif ($entityType === 'user') {
                    if ($entityId !== $user->getId()) {
                        return $this->json([
                            'error' => $translator->trans('Access denied')
                        ], Response::HTTP_FORBIDDEN);
                    }

                    $userImages = $imageRepository->findBy(['entityType' => 'user', 'entityId' => $user->getId()]);
                    if (count($userImages) >= self::MAX_USER_IMAGES) {
                        return $this->json([
                            'error' => $translator->trans('Maximum number of images reached')
                        ], Response::HTTP_BAD_REQUEST);
                    }
                } elseif ($entityType === 'category') {
                    if ($user->getRole() !== UserRole::ROLE_ADMIN) {
                        return $this->json([
                            'error' => $translator->trans('Access denied')
                        ], Response::HTTP_FORBIDDEN);
                    }

                    $categoryImages = $imageRepository->findBy(['entityType' => 'category', 'entityId' => $entityId]);
                    if (count($categoryImages) >= self::MAX_CATEGORY_IMAGES) {
                        return $this->json([
                            'error' => $translator->trans('Maximum number of images reached')
                        ], Response::HTTP_BAD_REQUEST);
                    }
                }
            }

            // Generate unique filename
            $baseName = Uuid::v4()->toString();

            // Process and save images
            $this->processImages($file, $baseName, $filterManager, $entityType, $entityId);

            $createdImages = $imageRepository->findBy(['baseName' => $baseName, 'entityType' => $entityType, 'entityId' => $entityId]);
            $imagePaths = [];
            /** @var Image $image */
            foreach ($createdImages as $image) {
                $imagePaths[$image->getSize()] = $this->generateImageUrl($image->getFullPath());
            }

            $response = ['values' => [$baseName => $imagePaths]];

            // Return response with image URLs
            return $this->json($response);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $translator->trans('Internal server error')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/images', name: 'image_delete', methods: ['DELETE'])]
    public function deleteImage(
        Request $request,
        ImageRepository $imageRepository,
        OfferRepository $offerRepository,
        TranslatorInterface $translator
    ): JsonResponse {
        try {
            //TODO: parse request to dto
            //$dto = $serializer->deserialize($request->getContent(), DeleteRequestDto::class, 'json');

            /** @var User $user */
            $user = $this->security->getUser();

            // Get baseName from request
            $requestData = json_decode($request->getContent(), true);
            $baseName = $requestData['baseName'];
            if (!$baseName) {
                return $this->json([
                    'error' => $translator->trans('Base name is required')
                ], Response::HTTP_BAD_REQUEST);
            }

            // Find image
            $image = $imageRepository->findOneBy(['baseName' => $baseName]);
            if (!$image) {
                return $this->json([
                    'error' => $translator->trans('Image not found')
                ], Response::HTTP_NOT_FOUND);
            }

            // Check permissions
            if ($image->getEntityType() === 'offer') {
                $offer = $offerRepository->findOneBy(['id' => $image->getEntityId()]);
                if (!$offer || $offer->getUser() !== $user) {
                    return $this->json([
                        'error' => $translator->trans('Access denied')
                    ], Response::HTTP_FORBIDDEN);
                }
            } elseif ($image->getEntityType() === 'user') {
                if ($image->getEntityId() !== $user->getId()) {
                    return $this->json([
                        'error' => $translator->trans('Access denied')
                    ], Response::HTTP_FORBIDDEN);
                }
            } elseif ($image->getEntityType() === 'category') {
                if ($user->getRole() !== UserRole::ROLE_ADMIN) {
                    return $this->json([
                        'error' => $translator->trans('Access denied')
                    ], Response::HTTP_FORBIDDEN);
                }
            }

            // Delete image files
            $this->deleteImageFiles($baseName);

            // Remove from database
            $this->entityManager->remove($image);
            $this->entityManager->flush();

            return $this->json([
                'message' => $translator->trans('Image deleted successfully')
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $translator->trans('Internal server error')
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function processImages($file, string $baseName, FilterManager $filterManager, string $entityType, ?int $entityId): void
    {
        // Define filter names from configuration
        $filters = ['64x64', '320x320', '1024x1024'];

        // Process each size
        foreach ($filters as $filterName) {
            $this->processImageSize($file, $baseName, $filterName, $filterManager, $entityType, $entityId);
        }
    }

    private function processImageSize($file, string $baseName, string $filterName, FilterManager $filterManager, string $entityType, ?int $entityId): void
    {
        try {
            // Read file contents
            $fileContents = file_get_contents($file->getPathname());

            // Create BinaryInterface
            $binary = new Binary($fileContents, $file->getMimeType(), $file->getClientOriginalName());

            // Process image using configured filter
            $processedBinary = $filterManager->applyFilter($binary, $filterName);

            // Get processed image content
            $processedContent = $processedBinary->getContent();

            // Save image
            $directory = $this->getDirectory();

            // Get file extension based on original file
            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

            $filePath = sprintf(
                '%s/%s/%s/%s_%s.%s',
                $this->getParameter('kernel.environment') === 'test' ? 'test-images' : 'images',
                $entityType,
                $entityId ?: 'tmp',
                $filterName,
                $baseName,
                $extension
            );

            // Ensure directory exists
            $fs = new Filesystem();
            $fs->mkdir(dirname($directory . $filePath));

            // Save image
            file_put_contents($directory . $filePath, $processedContent);

            $this->createImageEntity($baseName, $entityType, $entityId, $filterName, $filePath);

        } catch (\Exception $e) {
            throw new \RuntimeException('Error processing image: ' . $e->getMessage(), 0, $e);
        }
    }

    private function deleteImageFiles(string $baseName): void
    {
        $images = $this->entityManager->getRepository(Image::class)
            ->findBy(['baseName' => $baseName]);

        foreach ($images as $image) {
            if (file_exists($image->getFullPath())) {
                unlink($image->getFullPath());
            }
            $this->entityManager->remove($image);
        }

        $this->entityManager->flush();
    }

    private function generateImageUrl(string $filePath): string
    {
        return sprintf('%s/%s', $this->getParameter('app.url'), $filePath);
    }

    private function getDirectory(): string
    {
        return sprintf('%s/public/', $this->getParameter('kernel.project_dir'));
    }

    private function createImageEntity(string $baseName, string $entityType, ?int $entityId, string $filterName, string $filePath): void
    {
        // Create image entity
        $image = new Image();
        $image->setBaseName($baseName);
        $image->setEntityType($entityType);
        $image->setEntityId($entityId);
        $image->setSize($filterName);
        $image->setFullPath($filePath);

        $this->entityManager->persist($image);
        $this->entityManager->flush();
    }
}
