<?php

declare(strict_types=1);

namespace App\Api\Categories;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Categories\CategoryFavoritesSidebarController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/categories/favorites/sidebar',
            controller: CategoryFavoritesSidebarController::class,
            read: false,
            deserialize: false,
            name: 'favorites_sidebar'
        ),
    ],
)]
final class CategoryFavoritesSidebarEndpoint
{
}

