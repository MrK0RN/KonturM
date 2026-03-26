<?php

declare(strict_types=1);

namespace App\Api\Products;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Products\ProductsPopularController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/products/popular',
            controller: ProductsPopularController::class,
            read: false,
            deserialize: false,
            name: 'products_popular'
        ),
    ],
)]
final class ProductsPopularEndpoint
{
}

