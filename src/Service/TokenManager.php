<?php

namespace PhilTenno\FileSyncGo\Service;

use Doctrine\ORM\EntityManagerInterface;
use PhilTenno\FileSyncGo\Repository\FilesyncTokenRepository;
use PhilTenno\FileSyncGo\Entity\FilesyncToken;

class TokenManager
{
    private EntityManagerInterface $em;
    private FilesyncTokenRepository $repo;

    public function __construct(EntityManagerInterface $em, FilesyncTokenRepository $repo)
    {
        $this->em = $em;
        $this->repo = $repo;
    }

    /**
     * Generate a URL-safe token (no padding), length <= 32, persist only hash + last chars.
     * Returns plaintext token (show once after creation).
     */
    public function generateToken(int $length = 32): string
    {
        if ($length < 8 || $length > 32) {
            $length = 32;
        }

        $raw = $this->generateUrlSafeRandom($length);
        $hash = password_hash($raw, PASSWORD_DEFAULT);

        // store last 4 chars for masked display (non-sensitive)
        $last = mb_substr($raw, -4);

        // Remove existing token(s) — keep single global token
        $current = $this->repo->findCurrent();
        if ($current) {
            $this->em->remove($current);
            $this->em->flush();
        }

        $entity = new FilesyncToken($hash, $last);
        $this->em->persist($entity);
        $this->em->flush();

        return $raw;
    }

    /**
     * Verify provided token against stored hash(s). Uses password_verify (time-constant).
     */
    public function verifyToken(string $token): bool
    {
        $tokens = $this->repo->findAll();
        foreach ($tokens as $row) {
            if (password_verify($token, $row->getTokenHash())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return masked representation, e.g. "xxxx...abcd" or null if none.
     */
    public function getMaskedToken(): ?string
    {
        $current = $this->repo->findCurrent();
        if (!$current) {
            return null;
        }

        $last = $current->getLastChars();
        return 'xxxx...'.($last ?? '****');
    }

    /**
     * Rotate token: generate new token, return plaintext for one-time display.
     */
    public function rotateToken(int $length = 32): string
    {
        return $this->generateToken($length);
    }

    private function generateUrlSafeRandom(int $length): string
    {
        // base64url: generate enough bytes then trim
        $bytes = random_bytes((int)ceil($length * 3 / 4));
        $b64 = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
        return substr($b64, 0, $length);
    }
}