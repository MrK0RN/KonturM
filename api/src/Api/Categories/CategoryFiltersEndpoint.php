<?php

declare(strict_types=1);

namespace App\Api\Categories;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Categories\CategoryFiltersController;
use App\Controller\Categories\CategoryFiltersDiscoverController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/categories/{slug}/filters',
            controller: CategoryFiltersController::class,
            read: false,
            deserialize: false,
            name: 'category_filters'
        ),
        new Get(
            uriTemplate: '/categories/{slug}/filters/discover',
            controller: CategoryFiltersDiscoverController::class,
            security: 'is_granted("ROLE_ADMIN")',
            read: false,
            deserialize: false,
            name: 'category_filters_discover'
        ),
    ],
)]
final class CategoryFiltersEndpoint
{
}

