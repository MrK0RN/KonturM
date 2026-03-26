<?php

declare(strict_types=1);

namespace App\Api\Categories;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Categories\CategoryFavoritesMainController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/categories/favorites/main',
            controller: CategoryFavoritesMainController::class,
            read: false,
            deserialize: false,
            name: 'favorites_main'
        ),
    ],
)]
final class CategoryFavoritesMainEndpoint
{
}

