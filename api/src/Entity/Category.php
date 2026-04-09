<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Validator\ParentCategoryAcceptsChildCategories;
use App\Validator\SubcategoriesOnlyWithoutProducts;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity]
#[ORM\Table(name: 'categories')]
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
class Category
{
    public const DISPLAY_MODE_SUBCATEGORIES_ONLY = 'subcategories_only';

    public const DISPLAY_MODE_PRODUCTS_ONLY = 'products_only';

    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private string $id;

    #[ORM\Column(type: 'guid', name: 'parent_id', nullable: true)]
    #[SerializedName('parent_id')]
    #[ParentCategoryAcceptsChildCategories]
    private ?string $parentId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[SerializedName('photo_alt')]
    private ?string $photoAlt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[SerializedName('is_favorite_main')]
    private bool $isFavoriteMain = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[SerializedName('is_favorite_sidebar')]
    private bool $isFavoriteSidebar = false;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[SerializedName('sort_order')]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'subcategories_only'])]
    #[SerializedName('display_mode')]
    #[SubcategoriesOnlyWithoutProducts]
    private string $displayMode = 'subcategories_only';

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[SerializedName('aggregate_products')]
    private bool $aggregateProducts = false;

    /** @var array<string, mixed>|null keys: string[] whitelist/order; labels: array<string,string> for UI */
    #[ORM\Column(type: 'json', nullable: true)]
    #[SerializedName('filter_config')]
    private ?array $filterConfig = null;

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

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setParentId(?string $parentId): self
    {
        $this->parentId = $parentId;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = strip_tags((string) $description);

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

    public function isIsFavoriteMain(): bool
    {
        return $this->isFavoriteMain;
    }

    public function setIsFavoriteMain(bool $isFavoriteMain): self
    {
        $this->isFavoriteMain = $isFavoriteMain;

        return $this;
    }

    public function isIsFavoriteSidebar(): bool
    {
        return $this->isFavoriteSidebar;
    }

    public function setIsFavoriteSidebar(bool $isFavoriteSidebar): self
    {
        $this->isFavoriteSidebar = $isFavoriteSidebar;

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

    public function getDisplayMode(): string
    {
        return $this->displayMode;
    }

    public function setDisplayMode(string $displayMode): self
    {
        $this->displayMode = $displayMode;

        return $this;
    }

    public function isAggregateProducts(): bool
    {
        return $this->aggregateProducts;
    }

    public function setAggregateProducts(bool $aggregateProducts): self
    {
        $this->aggregateProducts = $aggregateProducts;

        return $this;
    }

    public function getFilterConfig(): ?array
    {
        return $this->filterConfig;
    }

    public function setFilterConfig(?array $filterConfig): self
    {
        $this->filterConfig = $filterConfig;

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

