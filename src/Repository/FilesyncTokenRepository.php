<?php

namespace PhilTenno\FileSyncGo\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PhilTenno\FileSyncGo\Entity\FilesyncToken;

class FilesyncTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FilesyncToken::class);
    }

    /**
     * Return the current token row (or null). We expect at most one global token row.
     */
    public function findCurrent(): ?FilesyncToken
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}