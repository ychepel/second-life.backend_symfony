<?php

namespace App\Mapper;

use App\Entity\Image;
use App\Entity\Interface\EntityWithImage;
use App\Repository\ImageRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

abstract class MappingEntityWithImage
{
    public function __construct(
        protected readonly ImageRepository $imageRepository,
        private readonly ContainerBagInterface $params
    ) { }

    protected function getImages(EntityWithImage $entity): array
    {
        $entityType = strtolower(basename(str_replace('\\', '/', get_class($entity))));
        $entityId = $entity->getId();

        $images = $this->imageRepository->findBy(['entityType' => $entityType, 'entityId' => $entityId]);
        $values = [];
        /** @var Image $image */
        foreach ($images as $image) {
            $values[$image->getBaseName()][$image->getSize()] = $this->generateImageUrl($image->getFullPath());
        }

        return ['values' => $values];
    }

    private function generateImageUrl(string $filePath): string
    {
        return sprintf('%s/%s', $this->params->get('app.url'), $filePath);
    }
}