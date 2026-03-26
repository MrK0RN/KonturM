<?php

declare(strict_types=1);

namespace App\Controller\Search;

use App\Service\SearchService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class SearchController
{
    public function __construct(private readonly SearchService $searchService)
    {
    }

    public function __invoke(Request $request): array
    {
        $q = trim((string) $request->query->get('q', ''));
        if (mb_strlen($q) < 2) {
            throw new BadRequestHttpException('Query parameter "q" must be at least 2 characters.');
        }

        $type = (string) $request->query->get('type', 'all');
        if (!in_array($type, ['all', 'products', 'services', 'categories'], true)) {
            throw new BadRequestHttpException('Invalid "type".');
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(max(1, (int) $request->query->get('limit', 20)), 100);

        $filters = [];
        $filtersRaw = $request->query->get('filters');
        if (is_string($filtersRaw)) {
            $decoded = json_decode($filtersRaw, true);
            if (is_array($decoded)) {
                $filters = $decoded;
            }
        } elseif (is_array($filtersRaw)) {
            $filters = $filtersRaw;
        }

        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');

        return $this->searchService->search(
            q: $q,
            type: $type,
            page: $page,
            limit: $limit,
            filters: $filters,
            minPrice: $minPrice !== null ? (string) $minPrice : null,
            maxPrice: $maxPrice !== null ? (string) $maxPrice : null,
        );
    }
}

