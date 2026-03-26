<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Service\CartService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
final class UpdateCartItemController
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    public function __invoke(string $item_id, Request $request): array
    {
        $payload = json_decode((string) $request->getContent(), true);
        $quantity = is_array($payload) ? (int) ($payload['quantity'] ?? 0) : 0;
        if ($quantity < 0) {
            throw new BadRequestHttpException('Quantity must be zero or positive.');
        }

        return $this->cartService->updateItem($item_id, $quantity);
    }
}

