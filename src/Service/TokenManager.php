<?php

namespace PhilTenno\FileSyncGo\Service;

use Contao\DataContainer;
use Contao\System;
use Doctrine\ORM\EntityManagerInterface;
use PhilTenno\FileSyncGo\Entity\FilesyncToken;
use PhilTenno\FileSyncGo\Repository\FilesyncTokenRepository;
use Psr\Log\LoggerInterface;

class TokenManager
{
    private ?EntityManagerInterface $em;
    private ?FilesyncTokenRepository $repo;
    private ?LoggerInterface $logger;

    public function __construct(
        ?EntityManagerInterface $em = null,
        ?FilesyncTokenRepository $repo = null,
        ?LoggerInterface $logger = null
    ) {
        // allow zero-arg instantiation (DCA callbacks), but keep DI when provided
        $this->em = $em;
        $this->repo = $repo;
        $this->logger = $logger;
    }

    /**
     * Ensure required services are available. Called lazily.
     */
    private function ensureDependencies(): void
    {
        if ($this->em !== null && $this->repo !== null && $this->logger !== null) {
            return;
        }

        $container = System::getContainer();

        if ($this->em === null) {
            $this->em = $container->get(EntityManagerInterface::class);
        }

        if ($this->repo === null) {
            // Try to get the repository service, otherwise fetch from entity manager
            if ($container->has(FilesyncTokenRepository::class)) {
                $this->repo = $container->get(FilesyncTokenRepository::class);
            } else {
                $this->repo = $this->em->getRepository(FilesyncToken::class);
            }
        }

        if ($this->logger === null) {
            // Prefer a dedicated channel if available, fallback to 'logger'
            if ($container->has('monolog.logger.contao.filesyncgo')) {
                $this->logger = $container->get('monolog.logger.contao.filesyncgo');
            } else {
                $this->logger = $container->get('logger');
            }
        }
    }

    /**
     * Generate a URL-safe token (no padding), length <= 32, persist only hash + last chars.
     * Returns plaintext token (show once after creation).
     */
    public function generateToken(int $length = 32): string
    {
        $this->ensureDependencies();

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
     * Verify provided token (boolean).
     */
    public function verifyToken(string $token): bool
    {
        $this->ensureDependencies();
        return null !== $this->findTokenEntityByPlain($token);
    }

    /**
     * Find and return the matching FilesyncToken entity for a plaintext token,
     * or null if not found. Uses password_verify (time-constant).
     */
    public function findTokenEntityByPlain(string $token): ?FilesyncToken
    {
        $this->ensureDependencies();

        $rows = $this->repo->findAll();
        foreach ($rows as $row) {
            if (password_verify($token, $row->getTokenHash())) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Return masked representation, e.g. "xxxx...abcd" or null if none.
     */
    public function getMaskedToken(): ?string
    {
        $this->ensureDependencies();

        $current = $this->repo->findCurrent();
        if (!$current) {
            return null;
        }

        $last = $current->getLastChars();
        return 'xxxx...' . ($last ?? '****');
    }

    /**
     * Rotate token: generate new token, return plaintext for one-time display.
     */
    public function rotateToken(int $length = 32): string
    {
        return $this->generateToken($length);
    }

    /**
     * Save callback for tl_settings.filesyncgo_token
     *
     * Stores a secure hash of the provided token. Returns the hash that will be persisted.
     *
     * @param string $value
     * @param DataContainer $dc
     * @return string hashed value to be stored
     * @throws \InvalidArgumentException on invalid input
     */
    public function saveTokenCallback($value, DataContainer $dc): string
    {
        // no deps needed here
        $token = trim((string) $value);

        // empty value => clear the stored token
        if ($token === '') {
            return '';
        }

        // Validation: max length 32 chars
        if (mb_strlen($token) > 32) {
            throw new \InvalidArgumentException('Token must be at most 32 characters long.');
        }

        // Hash the token using PHP password_hash (time-constant verify via password_verify)
        $hash = password_hash($token, PASSWORD_DEFAULT);

        // Never log the token or hash
        return $hash;
    }

    /**
     * Load callback for tl_settings.filesyncgo_token
     *
     * Prevents exposing the stored hash in the backend form.
     * We return an empty string so the field appears empty in the form.
     *
     * @param string $value
     * @param DataContainer $dc
     * @return string
     */
    public function loadTokenCallback($value, DataContainer $dc): string
    {
        // Do not show the stored hash in the backend.
        return '';
    }

    /**
     * Verify plain token against stored hash (used by TokenVerifier).
     */
    public function verifyPlainToken(string $plain, string $storedHash): bool
    {
        return password_verify($plain, $storedHash);
    }

    private function generateUrlSafeRandom(int $length): string
    {
        $bytes = random_bytes((int)ceil($length * 3 / 4));
        $b64 = rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
        return substr($b64, 0, $length);
    }
}