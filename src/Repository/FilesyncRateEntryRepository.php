<?php

namespace PhilTenno\FileSyncGo\Repository;

use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PhilTenno\FileSyncGo\Entity\FilesyncRateEntry;
use PhilTenno\FileSyncGo\Entity\FilesyncToken;

class FilesyncRateEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FilesyncRateEntry::class);
    }

    /**
     * Count requests for a token since a given DateTimeImmutable.
     */
    public function countRequestsSince(FilesyncToken $token, DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.token = :token')
            ->andWhere('r.requestedAt >= :since')
            ->setParameters([
                'token' => $token,
                'since' => $since,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }
}