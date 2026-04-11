<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Cache\CacheItemPoolInterface;

#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postRemove)]
final class CategoryFavoritesCacheInvalidator
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
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

        $this->cache->deleteItem('favorites_main');
        $this->cache->deleteItem('favorites_sidebar');
        $this->cache->deleteItem('favorites_combined');
    }
}
