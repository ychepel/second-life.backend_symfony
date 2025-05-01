<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

class EntityWithImageRepository extends ServiceEntityRepository
{
    private readonly string $className;

    public function __construct(ManagerRegistry $registry, private readonly LoggerInterface $logger)
    {
        $repositoryName = static::class;
        $this->className = 'App\Entity\\'.str_replace('Repository', '', basename(str_replace('\\', '/', $repositoryName)));

        parent::__construct($registry, $this->className);
    }

    public function findOneWithImage(array $conditions)
    {
        try {
            $result = null;
            foreach ($this->getQueryResults($conditions) as $object) {
                if ($object instanceof $this->className) {
                    $result = $object;
                } elseif (null !== $result && $object instanceof Image) {
                    $currentImages = $result->getImages();
                    $currentImages[] = $object;
                    $result->setImages($currentImages);
                }
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Cannot retrieve object with images', ['exception' => $e->getMessage()]);
        }

        return null;
    }

    public function getAllWithImages(): array
    {
        try {
            $collection = [];
            $index = -1;
            foreach ($this->getQueryResults() as $object) {
                if ($object instanceof $this->className) {
                    $collection[++$index] = $object;
                } elseif ($object instanceof Image) {
                    $currentCategory = $collection[$index];
                    $currentImages = $currentCategory->getImages();
                    $currentImages[] = $object;
                    $currentCategory->setImages($currentImages);
                }
            }

            return $collection;
        } catch (\Throwable $e) {
            $this->logger->error('Cannot retrieve objects with images', ['exception' => $e->getMessage()]);
        }

        return [];
    }

    private function getQueryResults(array $conditions = [])
    {
        $entityType = strtolower(basename(str_replace('\\', '/', $this->className)));
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('e', 'i')
            ->from($this->className, 'e')
            ->leftJoin(Image::class, 'i', Join::WITH, 'i.entityId = e.id AND i.entityType = :type')
            ->setParameter('type', $entityType);

        foreach ($conditions as $field => $value) {
            $queryBuilder->andWhere("e.$field = :$field")
                ->setParameter($field, $value);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
