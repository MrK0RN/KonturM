<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PageVisitRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: PageVisitRepository::class)]
#[ORM\Table(name: 'page_visits')]
#[ORM\Index(name: 'idx_page_visits_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_page_visits_path', columns: ['path'])]
class PageVisit
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private string $id;

    /** Request path (pathInfo), e.g. / or /product */
    #[ORM\Column(type: 'string', length: 2048)]
    private string $path;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $path)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->path = $path;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
