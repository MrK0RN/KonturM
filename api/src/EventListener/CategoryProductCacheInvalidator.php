<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Category fields are embedded into product pages; category recommendation edits must refresh them.
 */
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postRemove)]
final class CategoryProductCacheInvalidator
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly Connection $connection,
    ) {
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->invalidateIfCategory($args->getObject());
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->invalidateIfCategory($args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->invalidateIfCategory($args->getObject());
    }

    private function invalidateIfCategory(object $entity): void
    {
        if (! $entity instanceof Category) {
            return;
        }

        $slugs = $this->connection->fetchFirstColumn(
            'SELECT slug FROM products WHERE category_id = :categoryId',
            ['categoryId' => $entity->getId()],
        );

        foreach ($slugs as $slug) {
            if (is_string($slug) && $slug !== '') {
                $this->cache->delete('product_'.$slug);
            }
        }
    }
}
