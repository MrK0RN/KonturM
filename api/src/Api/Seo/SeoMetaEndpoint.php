<?php

declare(strict_types=1);

namespace App\Api\Seo;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Seo\SeoMetaController;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/seo/{type}/{slug}',
        controller: SeoMetaController::class,
        read: false,
        deserialize: false,
        name: 'seo_meta',
    ),
])]
final class SeoMetaEndpoint
{
}

