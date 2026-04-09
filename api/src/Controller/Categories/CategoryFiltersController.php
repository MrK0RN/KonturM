<?php

declare(strict_types=1);

namespace App\Controller\Categories;

use App\Service\CategoryQueryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class CategoryFiltersController
{
    public function __construct(
        private readonly CategoryQueryService $queryService,
    ) {
    }

    public function __invoke(string $slug, Request $request): array
    {
        $aggregate = $this->parseBool($request->query->get('aggregate'), true);

        $available = $this->queryService->getAvailableFilters($slug, $aggregate);

        return [
            'filters' => $available['filters'],
            'filter_labels' => $available['filter_labels'],
            'filter_order' => $available['filter_order'],
        ];
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

