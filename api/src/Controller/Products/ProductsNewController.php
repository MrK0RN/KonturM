<?php

declare(strict_types=1);

namespace App\Controller\Products;

use App\Service\ProductQueryService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class ProductsNewController
{
    public function __construct(
        private readonly ProductQueryService $queryService,
    ) {
    }

    public function __invoke(Request $request): array
    {
        $limit = (int) ($request->query->get('limit', 8));
        $limit = min(max(1, $limit), 20);

        $products = $this->queryService->getNewProducts($limit);

        return array_slice($products, 0, $limit);
    }
}

