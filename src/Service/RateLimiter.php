<?php

namespace PhilTenno\FileSyncGo\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\LockMode;
use PhilTenno\FileSyncGo\Entity\FilesyncToken;
use PhilTenno\FileSyncGo\Entity\FilesyncRateEntry;
use PhilTenno\FileSyncGo\Repository\FilesyncRateEntryRepository;
use DateTimeImmutable;

class RateLimiter
{
    private EntityManagerInterface $em;
    private FilesyncRateEntryRepository $repo;
    private int $limit = 24;

    public function __construct(EntityManagerInterface $em, FilesyncRateEntryRepository $repo, int $limit = 24)
    {
        $this->em = $em;
        $this->repo = $repo;
        $this->limit = $limit;
    }

    /**
     * Atomically check & record a request for the given token.
     * Returns true if allowed, false if limit exceeded.
     */
    public function allowRequest(FilesyncToken $token): bool
    {
        $since = (new DateTimeImmutable())->modify('-24 hours');
        $conn = $this->em->getConnection();

        $conn->beginTransaction();
        try {
            // Load managed token and obtain DB row lock for atomicity
            $managedToken = $this->em->find(FilesyncToken::class, $token->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$managedToken) {
                $conn->rollBack();
                return false;
            }

            $count = $this->repo->countRequestsSince($managedToken, $since);

            if ($count >= $this->limit) {
                $conn->rollBack();
                return false;
            }

            $entry = new FilesyncRateEntry($managedToken);
            $this->em->persist($entry);
            $this->em->flush();

            $conn->commit();
            return true;
        } catch (\Throwable $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            throw $e;
        }
    }
}