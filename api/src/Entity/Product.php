<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Validator\CategoryAcceptsDirectProducts;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: 'is_granted("ROLE_ADMIN")'),
        new Put(security: 'is_granted("ROLE_ADMIN")'),
        new Delete(security: 'is_granted("ROLE_ADMIN")'),
    ],
)]
class Product
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private string $id;

    #[ORM\Column(type: 'guid', name: 'category_id')]
    #[SerializedName('category_id')]
    #[CategoryAcceptsDirectProducts]
    private string $categoryId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $slug;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $article = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[SerializedName('photo_alt')]
    private ?string $photoAlt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'jsonb', nullable: true)]
    #[SerializedName('technical_specs')]
    private ?array $technicalSpecs = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'on_order'])]
    #[SerializedName('stock_status')]
    private string $stockStatus = 'on_order';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[SerializedName('manufacturing_time')]
    private ?string $manufacturingTime = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[SerializedName('gost_number')]
    private ?string $gostNumber = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[SerializedName('has_verification')]
    private bool $hasVerification = false;

    #[ORM\Column(type: 'jsonb', nullable: true)]
    private ?array $drawings = null;

    #[ORM\Column(type: 'jsonb', nullable: true)]
    private ?array $documents = null;

    #[ORM\Column(type: 'jsonb', nullable: true)]
    private ?array $certificates = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[SerializedName('meta_title')]
    private ?string $metaTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[SerializedName('meta_description')]
    private ?string $metaDescription = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    #[SerializedName('created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    #[SerializedName('updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        if (!isset($this->createdAt)) {
            $this->createdAt = $now;
        }
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function setCategoryId(string $categoryId): self
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getArticle(): ?string
    {
        return $this->article;
    }

    public function setArticle(?string $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    public function getPhotoAlt(): ?string
    {
        return $this->photoAlt;
    }

    public function setPhotoAlt(?string $photoAlt): self
    {
        $this->photoAlt = $photoAlt;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = strip_tags((string) $description);

        return $this;
    }

    public function getTechnicalSpecs(): ?array
    {
        return $this->technicalSpecs;
    }

    public function setTechnicalSpecs(?array $technicalSpecs): self
    {
        $this->technicalSpecs = $technicalSpecs;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getStockStatus(): string
    {
        return $this->stockStatus;
    }

    public function setStockStatus(string $stockStatus): self
    {
        $this->stockStatus = $stockStatus;

        return $this;
    }

    public function getManufacturingTime(): ?string
    {
        return $this->manufacturingTime;
    }

    public function setManufacturingTime(?string $manufacturingTime): self
    {
        $this->manufacturingTime = $manufacturingTime;

        return $this;
    }

    public function getGostNumber(): ?string
    {
        return $this->gostNumber;
    }

    public function setGostNumber(?string $gostNumber): self
    {
        $this->gostNumber = $gostNumber;

        return $this;
    }

    public function isHasVerification(): bool
    {
        return $this->hasVerification;
    }

    public function setHasVerification(bool $hasVerification): self
    {
        $this->hasVerification = $hasVerification;

        return $this;
    }

    public function getDrawings(): ?array
    {
        return $this->drawings;
    }

    public function setDrawings(?array $drawings): self
    {
        $this->drawings = $drawings;

        return $this;
    }

    public function getDocuments(): ?array
    {
        return $this->documents;
    }

    public function setDocuments(?array $documents): self
    {
        $this->documents = $documents;

        return $this;
    }

    public function getCertificates(): ?array
    {
        return $this->certificates;
    }

    public function setCertificates(?array $certificates): self
    {
        $this->certificates = $certificates;

        return $this;
    }

    public function getMetaTitle(): ?string
    {
        return $this->metaTitle;
    }

    public function setMetaTitle(?string $metaTitle): self
    {
        $this->metaTitle = $metaTitle;

        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(?string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

