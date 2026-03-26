<?php

declare(strict_types=1);

namespace App\Controller\Categories;

use App\Service\CategoryQueryService;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;

#[AsController]
final class CategoryFavoritesMainController
{
    public function __construct(
        private readonly CategoryQueryService $queryService,
        private readonly CacheInterface $cache,
    ) {
    }

    public function __invoke(Request $request): array
    {
        return $this->cache->get('favorites_main', function (ItemInterface $item) {
            $item->expiresAfter(3600); // 1 hour

            return $this->queryService->getFavorites(true);
        });
    }
}

