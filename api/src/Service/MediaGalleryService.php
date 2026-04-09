<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Category;
use App\Entity\MediaAsset;
use App\Entity\Product;
use App\Repository\MediaAssetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Cache\CacheInterface;

final class MediaGalleryService
{
    private const MAX_BYTES = 8 * 1024 * 1024;

    /** @var list<string> */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MediaAssetRepository $mediaRepository,
        private readonly ImageProcessingService $imageProcessing,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAssets(string $ownerType, string $ownerId): array
    {
        $this->assertOwnerType($ownerType);
        $this->assertOwnerExists($ownerType, $ownerId);

        return array_map(fn (MediaAsset $m) => $this->serialize($m), $this->mediaRepository->findForOwner($ownerType, $ownerId));
    }

    /**
     * @return array<string, mixed>
     */
    public function upload(string $ownerType, string $ownerId, UploadedFile $file, ?string $alt): array
    {
        $this->assertOwnerType($ownerType);
        $this->assertOwnerExists($ownerType, $ownerId);

        if ($file->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Файл больше 8 МБ.');
        }

        $mime = (string) $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('Допустимы только JPEG, PNG, WebP и GIF.');
        }

        $binary = (string) file_get_contents($file->getPathname());
        if ($binary === '') {
            throw new \InvalidArgumentException('Пустой файл.');
        }

        $asset = new MediaAsset();
        $asset->setOwnerType($ownerType);
        $asset->setOwnerId($ownerId);
        $asset->setSortOrder($this->mediaRepository->getMaxSortOrder($ownerType, $ownerId) + 1);
        $asset->setAlt($alt !== null && $alt !== '' ? $alt : null);

        $id = $asset->getId();

        $written = $this->imageProcessing->writeWebpVariants($binary, $ownerType, $ownerId, $id);

        $asset->setPath($written['full_web_path']);
        $asset->setThumbPath($written['thumb_web_path']);

        if ($this->mediaRepository->findForOwner($ownerType, $ownerId) === []) {
            $this->clearPrimaryForOwner($ownerType, $ownerId);
            $asset->setIsPrimary(true);
        }

        $this->em->persist($asset);
        $this->em->flush();

        $this->syncCoverAndCaches($ownerType, $ownerId);

        return $this->serialize($asset);
    }

    /**
     * @param array{alt?: string|null, is_primary?: bool} $data
     *
     * @return array<string, mixed>
     */
    public function patch(string $id, array $data): array
    {
        $asset = $this->mediaRepository->find($id);
        if (! $asset instanceof MediaAsset) {
            throw new \InvalidArgumentException('Медиа не найдено.');
        }

        if (array_key_exists('alt', $data)) {
            $alt = $data['alt'];
            $asset->setAlt(is_string($alt) && $alt !== '' ? $alt : null);
        }

        if (array_key_exists('is_primary', $data)) {
            if ($data['is_primary'] === true) {
                $this->clearPrimaryForOwner($asset->getOwnerType(), $asset->getOwnerId());
                $asset->setIsPrimary(true);
            } elseif ($data['is_primary'] === false) {
                $asset->setIsPrimary(false);
            }
        }

        $this->em->flush();
        $this->syncCoverAndCaches($asset->getOwnerType(), $asset->getOwnerId());

        return $this->serialize($asset);
    }

    /**
     * @param list<string> $orderedIds
     */
    public function reorder(string $ownerType, string $ownerId, array $orderedIds): void
    {
        $this->assertOwnerType($ownerType);
        $this->assertOwnerExists($ownerType, $ownerId);

        $existing = $this->mediaRepository->findForOwner($ownerType, $ownerId);
        $byId = [];
        foreach ($existing as $m) {
            $byId[$m->getId()] = $m;
        }

        $order = 0;
        foreach ($orderedIds as $oid) {
            if (! isset($byId[$oid])) {
                continue;
            }
            $byId[$oid]->setSortOrder($order);
            ++$order;
        }

        foreach ($existing as $m) {
            if (! in_array($m->getId(), $orderedIds, true)) {
                $m->setSortOrder($order);
                ++$order;
            }
        }

        $this->em->flush();
        $this->syncCoverAndCaches($ownerType, $ownerId);
    }

    public function delete(string $id): void
    {
        $asset = $this->mediaRepository->find($id);
        if (! $asset instanceof MediaAsset) {
            throw new \InvalidArgumentException('Медиа не найдено.');
        }

        $ownerType = $asset->getOwnerType();
        $ownerId = $asset->getOwnerId();

        $this->imageProcessing->removeFiles($asset->getPath(), $asset->getThumbPath());
        $this->em->remove($asset);
        $this->em->flush();

        $this->syncCoverAndCaches($ownerType, $ownerId);
    }

    private function assertOwnerType(string $ownerType): void
    {
        if (! in_array($ownerType, [MediaAsset::OWNER_PRODUCT, MediaAsset::OWNER_CATEGORY], true)) {
            throw new \InvalidArgumentException('owner_type должен быть product или category.');
        }
    }

    private function assertOwnerExists(string $ownerType, string $ownerId): void
    {
        $repo = $this->ownerRepository($ownerType);
        if ($repo->find($ownerId) === null) {
            throw new \InvalidArgumentException('Связанная запись не найдена.');
        }
    }

    /**
     * @return ObjectRepository<Product|Category>
     */
    private function ownerRepository(string $ownerType): ObjectRepository
    {
        return $ownerType === MediaAsset::OWNER_PRODUCT
            ? $this->em->getRepository(Product::class)
            : $this->em->getRepository(Category::class);
    }

    private function clearPrimaryForOwner(string $ownerType, string $ownerId): void
    {
        foreach ($this->mediaRepository->findForOwner($ownerType, $ownerId) as $m) {
            $m->setIsPrimary(false);
        }
    }

    private function syncCoverAndCaches(string $ownerType, string $ownerId): void
    {
        $items = $this->mediaRepository->findForOwner($ownerType, $ownerId);
        $cover = $items[0] ?? null;

        if ($ownerType === MediaAsset::OWNER_PRODUCT) {
            $entity = $this->em->find(Product::class, $ownerId);
            if ($entity instanceof Product) {
                if ($cover !== null) {
                    $entity->setPhoto($cover->getPath());
                    $entity->setPhotoAlt($cover->getAlt());
                } elseif ($items === [] && $this->isManagedMediaPath($entity->getPhoto())) {
                    $entity->setPhoto(null);
                    $entity->setPhotoAlt(null);
                }
                $this->em->flush();
                $slug = $this->em->getConnection()->fetchOne('SELECT slug FROM products WHERE id = :id', ['id' => $ownerId]);
                if (is_string($slug) && $slug !== '') {
                    $this->cache->delete('product_'.$slug);
                }
                $this->cache->delete('products_popular');
                $this->cache->delete('products_new');
            }

            return;
        }

        $entity = $this->em->find(Category::class, $ownerId);
        if ($entity instanceof Category) {
            if ($cover !== null) {
                $entity->setPhoto($cover->getPath());
                $entity->setPhotoAlt($cover->getAlt());
            } elseif ($items === [] && $this->isManagedMediaPath($entity->getPhoto())) {
                $entity->setPhoto(null);
                $entity->setPhotoAlt(null);
            }
            $this->em->flush();
        }
    }

    private function isManagedMediaPath(?string $path): bool
    {
        return is_string($path) && str_starts_with($path, '/media/');
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(MediaAsset $m): array
    {
        return [
            'id' => $m->getId(),
            'owner_type' => $m->getOwnerType(),
            'owner_id' => $m->getOwnerId(),
            'url' => $m->getPath(),
            'thumb_url' => $m->getThumbPath(),
            'alt' => $m->getAlt(),
            'sort_order' => $m->getSortOrder(),
            'is_primary' => $m->isPrimary(),
            'created_at' => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
