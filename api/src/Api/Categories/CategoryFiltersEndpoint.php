<?php

declare(strict_types=1);

namespace App\Api\Categories;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Categories\CategoryFiltersController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/categories/{slug}/filters',
            controller: CategoryFiltersController::class,
            read: false,
            deserialize: false,
            name: 'category_filters'
        ),
    ],
)]
final class CategoryFiltersEndpoint
{
}

