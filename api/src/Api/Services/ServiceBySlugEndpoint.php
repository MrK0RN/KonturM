<?php

declare(strict_types=1);

namespace App\Api\Services;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Services\ServiceBySlugController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/services/by-slug/{slug}',
            controller: ServiceBySlugController::class,
            read: false,
            deserialize: false,
            name: 'service_by_slug'
        ),
    ],
)]
final class ServiceBySlugEndpoint
{
}

