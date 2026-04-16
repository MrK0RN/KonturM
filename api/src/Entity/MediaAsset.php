<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'media_assets')]
#[ORM\Index(name: 'idx_media_owner', columns: ['owner_type', 'owner_id'])]
#[ORM\HasLifecycleCallbacks]
class MediaAsset
{
    public const OWNER_PRODUCT = 'product';

    public const OWNER_CATEGORY = 'category';

    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 20, name: 'owner_type')]
    private string $ownerType;

    #[ORM\Column(type: 'guid', name: 'owner_id')]
    private string $ownerId;

    /** Public URL path, e.g. /media/product/{ownerId}/{id}_full.webp or _full.png */
    #[ORM\Column(type: 'string', length: 500)]
    private string $path;

    #[ORM\Column(type: 'string', length: 500, name: 'thumb_path')]
    private string $thumbPath;

    #[ORM\Column(type: 'integer', name: 'sort_order', options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'boolean', name: 'is_primary', options: ['default' => false])]
    private bool $isPrimary = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $alt = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (! isset($this->createdAt)) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOwnerType(): string
    {
        return $this->ownerType;
    }

    public function setOwnerType(string $ownerType): self
    {
        $this->ownerType = $ownerType;

        return $this;
    }

    public function getOwnerId(): string
    {
        return $this->ownerId;
    }

    public function setOwnerId(string $ownerId): self
    {
        $this->ownerId = $ownerId;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getThumbPath(): string
    {
        return $this->thumbPath;
    }

    public function setThumbPath(string $thumbPath): self
    {
        $this->thumbPath = $thumbPath;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;

        return $this;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): self
    {
        $this->alt = $alt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
