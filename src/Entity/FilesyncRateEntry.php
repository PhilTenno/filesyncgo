<?php

namespace PhilTenno\FileSyncGo\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use PhilTenno\FileSyncGo\Repository\FilesyncRateEntryRepository;
use PhilTenno\FileSyncGo\Entity\FilesyncToken;

#[ORM\Entity(repositoryClass: FilesyncRateEntryRepository::class)]
#[ORM\Table(name: "filesync_rate_entries")]
class FilesyncRateEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: FilesyncToken::class)]
    #[ORM\JoinColumn(name: "token_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private FilesyncToken $token;

    #[ORM\Column(type: "datetime_immutable", name: "requested_at")]
    private DateTimeImmutable $requestedAt;

    public function __construct(FilesyncToken $token)
    {
        $this->token = $token;
        $this->requestedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): FilesyncToken
    {
        return $this->token;
    }

    public function getRequestedAt(): DateTimeImmutable
    {
        return $this->requestedAt;
    }
}