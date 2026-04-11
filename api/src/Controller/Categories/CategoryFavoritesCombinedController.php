<?php

declare(strict_types=1);

namespace App\Controller\Categories;

use App\Service\CategoryQueryService;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Request;

#[AsController]
final class CategoryFavoritesCombinedController
{
    public function __construct(
        private readonly CategoryQueryService $queryService,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return array{main: list<array<string, mixed>>, sidebar: list<array<string, mixed>>}
     */
    public function __invoke(Request $request): array
    {
        return $this->cache->get('favorites_combined', function (ItemInterface $item) {
            $item->expiresAfter(3600);

            return [
                'main' => $this->queryService->getFavorites(true),
                'sidebar' => $this->queryService->getFavorites(false),
            ];
        });
    }
}
