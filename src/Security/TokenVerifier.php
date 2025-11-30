<?php

declare(strict_types=1);

namespace PhilTenno\FileSyncGo\Security;

use Doctrine\ORM\EntityManagerInterface;
use PhilTenno\FilesyncGo\Entity\FilesyncToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Verifies a bearer token against stored password_hashes.
 *
 * - Extracts "Authorization: Bearer <token>".
 * - Runs password_verify against all stored hashes but only decides after checking all entries
 *   (reduces timing side-channels when multiple tokens exist).
 * - Returns the matching FilesyncToken entity or null.
 */
class TokenVerifier
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Verify a raw token string. Returns the matched FilesyncToken entity or null.
     */
    public function verify(string $token): ?FilesyncToken
    {
        $repo = $this->em->getRepository(FilesyncToken::class);
        $entries = $repo->findAll();

        if (empty($entries)) {
            $this->logger->info('Token verification failed (no tokens configured).', ['action' => 'verify']);
            return null;
        }

        // Prepare a dummy hash to consume time when an entry lacks a valid hash.
        $dummyHash = password_hash(random_bytes(16), PASSWORD_DEFAULT);

        $matched = null;

        // Always iterate through all entries and call password_verify for each.
        foreach ($entries as $entry) {
            $hash = (string) $entry->getTokenHash();

            if ($hash === '') {
                // consume time, do not reveal anything
                password_verify($token, $dummyHash);
                continue;
            }

            if (password_verify($token, $hash)) {
                // don't return immediately — store match and continue to keep timing similar
                $matched = $entry;
            } else {
                // a non-matching verify also costs time — no-op otherwise
            }
        }

        $this->logger->info('Token verification attempt completed.', [
            'result' => $matched ? 'success' : 'failure',
            'note'   => 'No sensitive data logged.',
        ]);

        return $matched;
    }

    /**
     * Extract Bearer token from Request and verify it.
     * Returns FilesyncToken on success, null otherwise.
     */
    public function verifyRequest(Request $request): ?FilesyncToken
    {
        $header = $request->headers->get('Authorization', '');
        if (!preg_match('/^\s*Bearer\s+(.+)$/i', $header, $m)) {
            $this->logger->info('Token verification failed (missing or malformed Authorization header).', ['action' => 'verifyRequest']);
            return null;
        }

        $bearer = trim($m[1]);
        if ($bearer === '') {
            $this->logger->info('Token verification failed (empty bearer token).', ['action' => 'verifyRequest']);
            return null;
        }

        return $this->verify($bearer);
    }
}