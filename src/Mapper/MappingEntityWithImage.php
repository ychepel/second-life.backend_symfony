<?php

namespace App\Mapper;

use App\Entity\Image;
use App\Entity\Interface\EntityWithImage;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

abstract class MappingEntityWithImage
{
    public function __construct(private readonly ContainerBagInterface $params)
    { }

    protected function getImages(EntityWithImage $entity): array
    {
        $values = [];
        /** @var Image $image */
        foreach ($entity->getImages() as $image) {
            $values[$image->getBaseName()][$image->getSize()] = $this->generateImageUrl($image->getFullPath());
        }

        return ['values' => $values];
    }

    private function generateImageUrl(string $filePath): string
    {
        return sprintf('%s/%s', $this->params->get('app.url'), $filePath);
    }
}