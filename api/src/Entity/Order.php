<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use App\Controller\Orders\OrderPatchStatusController;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(security: 'is_granted("ROLE_ADMIN")'),
        new Get(security: 'is_granted("ROLE_ADMIN")'),
        new Patch(
            uriTemplate: '/orders/{id}/status',
            controller: OrderPatchStatusController::class,
            read: false,
            deserialize: false,
            security: 'is_granted("ROLE_ADMIN")',
            name: 'order_patch_status'
        ),
        new Put(security: 'is_granted("ROLE_ADMIN")'),
        new Delete(security: 'is_granted("ROLE_ADMIN")'),
    ],
)]
class Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 50, name: 'order_number', unique: true)]
    #[SerializedName('order_number')]
    private string $orderNumber;

    #[ORM\Column(type: 'string', length: 255, name: 'customer_name')]
    #[SerializedName('customer_name')]
    private string $customerName;

    #[ORM\Column(type: 'string', length: 255, name: 'customer_company', nullable: true)]
    #[SerializedName('customer_company')]
    private ?string $customerCompany = null;

    #[ORM\Column(type: 'string', length: 50, name: 'customer_phone')]
    #[SerializedName('customer_phone')]
    private string $customerPhone;

    #[ORM\Column(type: 'string', length: 255, name: 'customer_email')]
    #[SerializedName('customer_email')]
    private string $customerEmail;

    /**
     * Array of {type, id, name, article, quantity, price}.
     */
    #[ORM\Column(type: 'jsonb', name: 'items', options: ['jsonb' => true])]
    #[SerializedName('items')]
    private array $items = [];

    /**
     * Array of uploaded file URLs.
     */
    #[ORM\Column(type: 'jsonb', name: 'attachments', nullable: true)]
    #[SerializedName('attachments')]
    private ?array $attachments = null;

    #[ORM\Column(type: 'text', name: 'comment', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, name: 'total_amount', nullable: true)]
    #[SerializedName('total_amount')]
    private ?string $totalAmount = null;

    #[ORM\Column(type: 'string', length: 50, name: 'status', options: ['default' => 'new'])]
    #[SerializedName('status')]
    private string $status = 'new';

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

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): self
    {
        $this->customerName = $customerName;

        return $this;
    }

    public function getCustomerCompany(): ?string
    {
        return $this->customerCompany;
    }

    public function setCustomerCompany(?string $customerCompany): self
    {
        $this->customerCompany = $customerCompany;

        return $this;
    }

    public function getCustomerPhone(): string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(string $customerPhone): self
    {
        $this->customerPhone = $customerPhone;

        return $this;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): self
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function setAttachments(?array $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

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

