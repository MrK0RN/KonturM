<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Product;
use Doctrine\DBAL\Connection;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Сброс кеша публичного ответа GET /products/by-slug/{slug} (ProductQueryService, ключ product_{slug}).
 * Иначе после правок в админке до 24 ч отдаётся старое описание и поля.
 */
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postRemove)]
final class ProductPublicCacheInvalidator
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly Connection $connection,
    ) {
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (! $entity instanceof Product) {
            return;
        }

        $this->deleteProductCacheBySlug($entity->getSlug());
        $this->deleteProductCachesWhereRecommended((string) $entity->getId());

        $uow = $args->getObjectManager()->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($entity);
        if (isset($changeSet['slug'][0]) && is_string($changeSet['slug'][0]) && $changeSet['slug'][0] !== '') {
            $this->deleteProductCacheBySlug($changeSet['slug'][0]);
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (! $entity instanceof Product) {
            return;
        }

        $this->deleteProductCacheBySlug($entity->getSlug());
        $this->deleteProductCachesWhereRecommended((string) $entity->getId());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if (! $entity instanceof Product) {
            return;
        }

        $this->deleteProductCacheBySlug($entity->getSlug());
        $this->deleteProductCachesWhereRecommended((string) $entity->getId());
    }

    private function deleteProductCacheBySlug(string $slug): void
    {
        if ($slug === '') {
            return;
        }

        $this->cache->delete('product_'.$slug);
    }

    private function deleteProductCachesWhereRecommended(string $productId): void
    {
        if ($productId === '') {
            return;
        }

        $slugs = $this->connection->fetchFirstColumn(<<<SQL
SELECT DISTINCT p.slug
FROM products p
INNER JOIN categories c ON c.id = p.category_id
WHERE c.also_bought_product_ids @> CAST(:needle AS jsonb)
SQL, ['needle' => json_encode([$productId], JSON_THROW_ON_ERROR)]);

        foreach ($slugs as $slug) {
            if (is_string($slug) && $slug !== '') {
                $this->deleteProductCacheBySlug($slug);
            }
        }
    }
}
