<?php

declare(strict_types=1);

namespace App\Api\Cart;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Patch;
use App\Controller\Cart\UpdateCartItemController;

#[ApiResource(operations: [
    new Patch(
        uriTemplate: '/cart/items/{item_id}',
        controller: UpdateCartItemController::class,
        read: false,
        deserialize: false,
        write: false,
        name: 'cart_items_update',
    ),
])]
final class CartItemsUpdateEndpoint
{
}

