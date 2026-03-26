<?php

declare(strict_types=1);

namespace App\Api\Search;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Search\SearchController;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/search',
        controller: SearchController::class,
        read: false,
        deserialize: false,
        name: 'search_fulltext',
    ),
])]
final class SearchEndpoint
{
}

