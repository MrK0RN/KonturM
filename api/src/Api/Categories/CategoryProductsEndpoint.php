<?php

declare(strict_types=1);

namespace App\Api\Categories;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Categories\CategoryProductsController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/categories/{slug}/products',
            controller: CategoryProductsController::class,
            read: false,
            deserialize: false,
            name: 'category_products'
        ),
    ],
)]
final class CategoryProductsEndpoint
{
}

