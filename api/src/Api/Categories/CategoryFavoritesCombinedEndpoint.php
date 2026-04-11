<?php

declare(strict_types=1);

namespace App\Api\Categories;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Categories\CategoryFavoritesCombinedController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/categories/favorites',
            controller: CategoryFavoritesCombinedController::class,
            read: false,
            deserialize: false,
            name: 'favorites_combined'
        ),
    ],
)]
final class CategoryFavoritesCombinedEndpoint
{
}
