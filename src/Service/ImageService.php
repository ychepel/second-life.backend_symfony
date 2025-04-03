<?php

namespace App\Service;

use App\Exception\ServiceException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ImageService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    )
    {}

    /**
     * @throws ServiceException
     */
    public function attachImages(string $entityType, int $entityId, array $baseNames): void
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
    }
}