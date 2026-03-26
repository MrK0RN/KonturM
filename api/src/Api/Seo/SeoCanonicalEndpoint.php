<?php

declare(strict_types=1);

namespace App\Api\Seo;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Seo\SeoCanonicalController;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/seo/canonical',
        controller: SeoCanonicalController::class,
        read: false,
        deserialize: false,
        name: 'seo_canonical',
    ),
])]
final class SeoCanonicalEndpoint
{
}

