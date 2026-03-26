<?php

declare(strict_types=1);

namespace App\Api\Cart;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Controller\Cart\AddCartItemController;

#[ApiResource(operations: [
    new Post(
        uriTemplate: '/cart/items',
        controller: AddCartItemController::class,
        read: false,
        deserialize: false,
        write: false,
        name: 'cart_items_add',
    ),
])]
final class CartItemsAddEndpoint
{
}

