<?php

namespace App\Service;

use App\Entity\Image;
use App\Enum\UserRole;
use App\Exception\AccessException;
use App\Exception\ServiceException;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Model\Binary;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImageService
{
    private const int MAX_FILE_SIZE = 8388608;
    private const int MAX_OFFER_IMAGES = 5;
    private const int MAX_USER_IMAGES = 1;
    private const int MAX_CATEGORY_IMAGES = 1;
    private const array ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png'];
    private const string TMP_FOLDER_NAME = 'tmp';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
        private readonly string $appUrl,
        private readonly string $env,
        private readonly ValidatorInterface $validator,
        private readonly TranslatorInterface $translator,
        private readonly FilterManager $filterManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param string $entityType
     * @param int $entityId
     * @param array $baseNames
     * @return Image[]
     */
    public function attachImages(string $entityType, int $entityId, array $baseNames): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $query = $queryBuilder
            ->update('App\Entity\Image', 'i')
            ->set('i.entityId', ':newEntityId')
            ->set('i.updatedAt', ':updatedAt')
            ->where($queryBuilder->expr()->in('i.baseName', ':baseNames'))
            ->andWhere('i.entityType = :entityType')
            ->andWhere('i.entityId is NULL')
            ->setParameter('newEntityId', $entityId)
            ->setParameter('baseNames', $baseNames)
            ->setParameter('entityType', $entityType)
            ->setParameter('updatedAt', new \DateTime())
            ->getQuery();

        $updatedCount = $query->execute();

        if ($updatedCount === 0) {
            $this->logger->error(
                "Failed to attach images to entity `$entityType`",
                ['entityId' => $entityId, 'baseNames' => $baseNames]
            );
            throw new ServiceException("Failed to attach images to `$entityType`");
        }

        /** @var ImageRepository $imageRepository */
        $imageRepository = $this->entityManager->getRepository(Image::class);
        $images = $imageRepository->findAllByBaseNames($baseNames);
        $this->moveImagesToPermanentFolder($images, $entityId);

        return $images;
    }

    /**
     * @throws ServiceException
     */
    public function uploadImage(
        $file,
        string $entityType,
        ?int $entityId,
        $user,
        $imageRepository,
        $offerRepository
    ): array {
        if (!in_array($entityType, ['offer', 'user', 'category'])) {
            throw new ServiceException($this->translator->trans('Invalid entity type'));
        }
        if (!$file) {
            throw new ServiceException($this->translator->trans('File is required'));
        }

        $constraints = new File([
            'maxSize' => self::MAX_FILE_SIZE,
            'mimeTypes' => self::ALLOWED_IMAGE_TYPES,
            'mimeTypesMessage' => $this->translator->trans('Please upload a valid image file (JPEG or PNG)')
        ]);
        $errors = $this->validator->validate($file, $constraints);
        if (count($errors) > 0) {
            throw new ServiceException($errors[0]->getMessage());
        }

        if ($entityId !== null) {
            //TODO: refactor validation
            if ($entityType === 'offer') {
                $offer = $offerRepository->findOneBy(['id' => $entityId, 'user' => $user]);
                if (!$offer) {
                    throw new AccessException($this->translator->trans('Access denied'));
                }
                $offerImages = $imageRepository->findBy(['entityType' => 'offer', 'entityId' => $entityId]);
                if (count($offerImages) >= self::MAX_OFFER_IMAGES) {
                    throw new ServiceException($this->translator->trans('Maximum number of images reached'));
                }
            } elseif ($entityType === 'user') {
                if ($entityId !== $user->getId()) {
                    throw new AccessException($this->translator->trans('Access denied'));
                }
                $userImages = $imageRepository->findBy(['entityType' => 'user', 'entityId' => $user->getId()]);
                if (count($userImages) >= self::MAX_USER_IMAGES) {
                    throw new ServiceException($this->translator->trans('Maximum number of images reached'));
                }
            } elseif ($entityType === 'category') {
                if ($user->getRole() !== UserRole::ROLE_ADMIN) {
                    throw new AccessException($this->translator->trans('Access denied'));
                }
                $categoryImages = $imageRepository->findBy(['entityType' => 'category', 'entityId' => $entityId]);
                if (count($categoryImages) >= self::MAX_CATEGORY_IMAGES) {
                    throw new ServiceException($this->translator->trans('Maximum number of images reached'));
                }
            }
        }

        $baseName = Uuid::v4()->toString();
        $this->processImages($file, $baseName, $entityType, $entityId);
        $createdImages = $imageRepository->findBy(['baseName' => $baseName, 'entityType' => $entityType, 'entityId' => $entityId]);
        $imagePaths = [];
        foreach ($createdImages as $image) {
            $imagePaths[$image->getSize()] = $this->generateImageUrl($image->getFullPath());
        }

        return ['baseName' => $baseName, 'imagePaths' => $imagePaths];
    }

    /**
     * @throws ServiceException
     */
    public function deleteImage(
        string $baseName,
        $user,
        $imageRepository,
        $offerRepository
    ): void {
        $image = $imageRepository->findOneBy(['baseName' => $baseName]);
        if (!$image) {
            throw new ServiceException($this->translator->trans('Image not found'));
        }

        if ($image->getEntityType() === 'offer') {
            $offer = $offerRepository->findOneBy(['id' => $image->getEntityId()]);
            if (!$offer || $offer->getUser() !== $user) {
                throw new AccessException($this->translator->trans('Access denied'));
            }
        } elseif ($image->getEntityType() === 'user') {
            if ($image->getEntityId() !== $user->getId()) {
                throw new AccessException($this->translator->trans('Access denied'));
            }
        } elseif ($image->getEntityType() === 'category') {
            if ($user->getRole() !== UserRole::ROLE_ADMIN) {
                throw new AccessException($this->translator->trans('Access denied'));
            }
        }
        $this->deleteImageFiles($baseName);
        $this->entityManager->remove($image);
        $this->entityManager->flush();
    }

    private function processImages($file, string $baseName, string $entityType, ?int $entityId): void
    {
        $filters = ['64x64', '320x320', '1024x1024'];
        foreach ($filters as $filterName) {
            $this->processImageSize($file, $baseName, $filterName, $entityType, $entityId);
        }
    }

    private function processImageSize($file, string $baseName, string $filterName, string $entityType, ?int $entityId): void
    {
        try {
            $fileContents = file_get_contents($file->getPathname());
            $binary = new Binary($fileContents, $file->getMimeType(), $file->getClientOriginalName());
            $processedBinary = $this->filterManager->applyFilter($binary, $filterName);
            $processedContent = $processedBinary->getContent();
            $directory = $this->getDirectory();
            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $filePath = sprintf(
                '%s/%s/%s/%s_%s.%s',
                $this->env === 'test' ? 'test-images' : 'images',
                $entityType,
                $entityId ?: self::TMP_FOLDER_NAME,
                $filterName,
                $baseName,
                $extension
            );
            $fs = new Filesystem();
            $fs->mkdir(dirname($directory . $filePath));
            file_put_contents($directory . $filePath, $processedContent);
            $this->createImageEntity($baseName, $entityType, $entityId, $filterName, $filePath);
        } catch (\Exception $e) {
            throw new ServiceException('Error processing image: ' . $e->getMessage(), 0, $e);
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
        return sprintf('%s/%s', $this->appUrl, $filePath);
    }

    private function getDirectory(): string
    {
        return sprintf('%s/public/', $this->projectDir);
    }

    private function createImageEntity(string $baseName, string $entityType, ?int $entityId, string $filterName, string $filePath): void
    {
        $image = new Image();
        $image->setBaseName($baseName);
        $image->setEntityType($entityType);
        $image->setEntityId($entityId);
        $image->setSize($filterName);
        $image->setFullPath($filePath);
        $this->entityManager->persist($image);
        $this->entityManager->flush();
    }

    private function moveImagesToPermanentFolder(array $images): void
    {
        /** @var Image $image */
        foreach ($images as $image) {
            $oldPath = $image->getFullPath();
            $newPath = str_replace('/' . self::TMP_FOLDER_NAME . '/', "/{$image->getEntityId()}/", $oldPath);
            $fs = new Filesystem();
            $targetDir = dirname($newPath);

            try {
                if (!$fs->exists($targetDir)) {
                    $fs->mkdir($targetDir);
                }
                $fs->copy($oldPath, $newPath);
            } catch (IOException $e) {
                $this->logger->error('Cannot move image', [
                    'exception' => $e->getMessage(),
                    'imageId' => $image->getId(),
                    'entityId' => $image->getEntityId()]);
                return;
            }

            $image->setFullPath($newPath);
            $this->entityManager->persist($image);
            $this->entityManager->flush();
        }
    }
}