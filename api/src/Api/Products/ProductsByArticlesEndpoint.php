<?php

declare(strict_types=1);

namespace App\Api\Products;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Products\ProductsByArticlesController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/products/by-articles',
            controller: ProductsByArticlesController::class,
            read: false,
            deserialize: false,
            name: 'products_by_articles'
        ),
    ],
)]
final class ProductsByArticlesEndpoint
{
}

