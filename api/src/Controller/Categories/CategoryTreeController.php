<?php

declare(strict_types=1);

namespace App\Controller\Categories;

use App\Service\CategoryQueryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AsController]
final class CategoryTreeController
{
    public function __construct(
        private readonly CategoryQueryService $queryService,
        private readonly CacheInterface $cache,
    ) {
    }

    public function __invoke(Request $request): array
    {
        $includeProducts = $this->parseBool($request->query->get('include_products'), false);
        $maxDepthRaw = $request->query->get('max_depth');
        $maxDepth = $maxDepthRaw === null ? null : max(0, (int) $maxDepthRaw);

        $cacheKey = 'category_tree';
        if ($includeProducts || $maxDepth !== null) {
            $cacheKey = 'category_tree_' . md5(json_encode(['includeProducts' => $includeProducts, 'maxDepth' => $maxDepth], JSON_UNESCAPED_UNICODE));
        }

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($includeProducts, $maxDepth) {
            $item->expiresAfter(3600); // 1 hour

            return $this->queryService->getCategoryTree($includeProducts, $maxDepth);
        });
    }

    private function parseBool(mixed $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $default;
    }
}

