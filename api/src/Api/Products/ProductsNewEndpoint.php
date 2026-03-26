<?php

declare(strict_types=1);

namespace App\Api\Products;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Products\ProductsNewController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/products/new',
            controller: ProductsNewController::class,
            read: false,
            deserialize: false,
            name: 'products_new'
        ),
    ],
)]
final class ProductsNewEndpoint
{
}

