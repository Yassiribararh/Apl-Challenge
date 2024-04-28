<?php

declare(strict_types=1);

namespace App\Entity;

use App\Dto\ImagesDto;
use App\Repository\ImagesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ImagesRepository::class)]
class Images
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 1000)]
    private string $type;

    #[ORM\Column(type: 'string', length: 1000)]
    private string $path;

    #[ORM\Column(type: 'boolean', length: 1000)]
    private bool $offlineMode;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $name,
        string $path,
        string $type,
        bool $offlineMode,
    ) {
        $this->id = Uuid::v4();
        $this->name = $name;
        $this->path = $path;
        $this->type = $type;
        $this->offlineMode = $offlineMode;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function createFromDto(ImagesDto $uploadedImagesDto): self
    {
        return new self(...(array) $uploadedImagesDto);
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $type;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;

        return $path;
    }

    public function getOfflineMode(): bool
    {
        return $this->offlineMode;
    }

    public function setOfflineMode($offlineMode)
    {
        $this->offlineMode = $offlineMode;

        return $offlineMode;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
