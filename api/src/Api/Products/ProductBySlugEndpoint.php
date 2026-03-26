<?php

declare(strict_types=1);

namespace App\Api\Products;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Products\ProductBySlugController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/products/by-slug/{slug}',
            controller: ProductBySlugController::class,
            read: false,
            deserialize: false,
            name: 'product_by_slug'
        ),
    ],
)]
final class ProductBySlugEndpoint
{
}

