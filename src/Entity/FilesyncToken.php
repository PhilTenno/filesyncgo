<?php

namespace PhilTenno\FileSyncGo\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use PhilTenno\FileSyncGo\Repository\FilesyncTokenRepository;

#[ORM\Entity(repositoryClass: FilesyncTokenRepository::class)]
#[ORM\Table(name: "filesync_tokens")]
class FilesyncToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    // hashed token (password_hash)
    #[ORM\Column(type: "string", length: 255, name: "token_hash")]
    private string $tokenHash;

    // store last chars only (non‑sensitive) to show masked token in UI
    #[ORM\Column(type: "string", length: 8, nullable: true, name: "last_chars")]
    private ?string $lastChars = null;

    #[ORM\Column(type: "datetime_immutable", name: "created_at")]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: "datetime_immutable", nullable: true, name: "updated_at")]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(string $tokenHash, ?string $lastChars = null)
    {
        $this->tokenHash = $tokenHash;
        $this->lastChars = $lastChars;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function setTokenHash(string $hash): void
    {
        $this->tokenHash = $hash;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getLastChars(): ?string
    {
        return $this->lastChars;
    }

    public function setLastChars(?string $chars): void
    {
        $this->lastChars = $chars;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}