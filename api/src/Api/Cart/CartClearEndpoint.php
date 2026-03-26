<?php

declare(strict_types=1);

namespace App\Api\Cart;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use App\Controller\Cart\ClearCartController;

#[ApiResource(operations: [
    new Delete(
        uriTemplate: '/cart',
        controller: ClearCartController::class,
        read: false,
        deserialize: false,
        write: false,
        name: 'cart_clear',
    ),
])]
final class CartClearEndpoint
{
}

