<?php

declare(strict_types=1);

namespace App\Api\Cart;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use App\Controller\Cart\DeleteCartItemController;

#[ApiResource(operations: [
    new Delete(
        uriTemplate: '/cart/items/{item_id}',
        controller: DeleteCartItemController::class,
        read: false,
        deserialize: false,
        write: false,
        name: 'cart_items_delete',
    ),
])]
final class CartItemsDeleteEndpoint
{
}

