<?php

declare(strict_types=1);

namespace App\Controller\Products;

use App\Service\ProductQueryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
final class ProductBySlugController
{
    public function __construct(
        private readonly ProductQueryService $queryService,
    ) {
    }

    public function __invoke(string $slug, Request $request): JsonResponse
    {
        $product = $this->queryService->getProductBySlug($slug);
        if ($product === null) {
            throw new NotFoundHttpException(sprintf('Product "%s" not found.', $slug));
        }

        return new JsonResponse($product);
    }
}

