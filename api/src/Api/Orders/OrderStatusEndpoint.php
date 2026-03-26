<?php

declare(strict_types=1);

namespace App\Api\Orders;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Orders\OrderStatusController;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/orders/{order_number}/status',
            controller: OrderStatusController::class,
            read: false,
            deserialize: false,
            name: 'order_status_public'
        ),
    ],
)]
final class OrderStatusEndpoint
{
}

