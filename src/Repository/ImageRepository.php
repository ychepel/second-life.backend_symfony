<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    public function findAllByBaseNames(array $baseNames): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.baseName IN (:baseNames)')
            ->setParameter('baseNames', $baseNames)
            ->getQuery()
            ->getResult();
    }
}
