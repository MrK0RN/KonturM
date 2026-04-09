<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MediaAsset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MediaAsset>
 */
final class MediaAssetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MediaAsset::class);
    }

    /**
     * @return MediaAsset[]
     */
    public function findForOwner(string $ownerType, string $ownerId): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.ownerType = :t')
            ->andWhere('m.ownerId = :o')
            ->setParameter('t', $ownerType)
            ->setParameter('o', $ownerId)
            ->orderBy('m.isPrimary', 'DESC')
            ->addOrderBy('m.sortOrder', 'ASC')
            ->addOrderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getMaxSortOrder(string $ownerType, string $ownerId): int
    {
        $v = $this->createQueryBuilder('m')
            ->select('MAX(m.sortOrder)')
            ->andWhere('m.ownerType = :t')
            ->andWhere('m.ownerId = :o')
            ->setParameter('t', $ownerType)
            ->setParameter('o', $ownerId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $v;
    }
}
