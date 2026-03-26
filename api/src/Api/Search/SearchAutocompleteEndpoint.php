<?php

declare(strict_types=1);

namespace App\Api\Search;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Search\SearchAutocompleteController;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/search/autocomplete',
        controller: SearchAutocompleteController::class,
        read: false,
        deserialize: false,
        name: 'search_autocomplete',
    ),
])]
final class SearchAutocompleteEndpoint
{
}

