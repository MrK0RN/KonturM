<?php

declare(strict_types=1);

namespace App\Api\Cart;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\Cart\CheckoutCartController;

#[ApiResource(operations: [
    new Post(
        uriTemplate: '/cart/checkout',
        controller: CheckoutCartController::class,
        read: false,
        deserialize: false,
        write: false,
        name: 'cart_checkout',
    ),
])]
final class CartCheckoutEndpoint
{
}

