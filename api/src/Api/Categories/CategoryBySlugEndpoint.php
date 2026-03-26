<?php

declare(strict_types=1);

namespace App\Api\Categories;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Categories\CategoryBySlugController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/categories/by-slug/{slug}',
            controller: CategoryBySlugController::class,
            read: false,
            deserialize: false,
            name: 'category_by_slug'
        ),
    ],
)]
final class CategoryBySlugEndpoint
{
}

