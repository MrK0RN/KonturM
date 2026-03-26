<?php

declare(strict_types=1);

namespace App\Api\Categories;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Categories\CategoryTreeController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/categories/tree',
            controller: CategoryTreeController::class,
            read: false,
            deserialize: false,
            name: 'category_tree'
        ),
    ],
)]
final class CategoryTreeEndpoint
{
}

