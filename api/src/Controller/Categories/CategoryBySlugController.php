<?php

declare(strict_types=1);

namespace App\Controller\Categories;

use App\Service\CategoryQueryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class CategoryBySlugController
{
    public function __construct(
        private readonly CategoryQueryService $queryService,
    ) {
    }

    public function __invoke(string $slug, Request $request): array
    {
        $includeChildren = $this->parseBool($request->query->get('include_children'), false);
        $includeProducts = $this->parseBool($request->query->get('include_products'), false);

        $category = $this->queryService->getCategoryBySlug($slug, $includeChildren, $includeProducts);
        if ($category === null) {
            // API Platform will convert thrown exceptions to a proper 404 response.
            throw $this->createNotFound($slug);
        }

        return $category;
    }

    private function parseBool(mixed $value, bool $default): bool
    {
        if ($value === null) {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $default;
    }

    private function createNotFound(string $slug): \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
    {
        return new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(sprintf('Category "%s" not found.', $slug));
    }
}

