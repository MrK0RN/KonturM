<?php

declare(strict_types=1);

namespace App\Controller\Categories;

use App\Service\CategoryQueryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class CategoryProductsController
{
    public function __construct(
        private readonly CategoryQueryService $queryService,
    ) {
    }

    public function __invoke(string $slug, Request $request): array
    {
        $page = max(1, (int) ($request->query->get('page', 1)));
        $limit = (int) ($request->query->get('limit', 20));
        $limit = min(max(1, $limit), 100);

        $sort = (string) ($request->query->get('sort', 'created_at'));
        $order = strtolower((string) ($request->query->get('order', 'desc')));
        $order = $order === 'asc' ? 'asc' : 'desc';

        $aggregate = $this->parseBool($request->query->get('aggregate'), false);
        $filters = $this->parseFilters($request->query->get('filters'));

        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $minPrice = $minPrice === null ? null : (string) $minPrice;
        $maxPrice = $maxPrice === null ? null : (string) $maxPrice;

        $search = $request->query->get('search');
        $search = $search === null ? null : (string) $search;

        return $this->queryService->getProductsForCategory(
            slug: $slug,
            page: $page,
            limit: $limit,
            sort: $sort,
            order: $order,
            aggregate: $aggregate,
            filters: $filters,
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            search: $search,
        );
    }

    private function parseBool(mixed $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $default;
    }

    private function parseFilters(mixed $filtersParam): array
    {
        if ($filtersParam === null) {
            return [];
        }

        if (is_string($filtersParam)) {
            $decoded = json_decode($filtersParam, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($filtersParam)) {
            return $filtersParam;
        }

        return [];
    }
}

