<?php

declare(strict_types=1);

namespace App\Api\Orders;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\Orders\CreateOrderController;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/orders',
            controller: CreateOrderController::class,
            read: false,
            deserialize: false,
            write: false,
            name: 'create_order_public'
        ),
    ],
)]
final class CreateOrderEndpoint
{
}

