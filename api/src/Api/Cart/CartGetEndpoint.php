<?php

declare(strict_types=1);

namespace App\Api\Cart;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Controller\Cart\GetCartController;

#[ApiResource(operations: [
    new Get(
        uriTemplate: '/cart',
        controller: GetCartController::class,
        read: false,
        deserialize: false,
        name: 'cart_get',
    ),
])]
final class CartGetEndpoint
{
}

