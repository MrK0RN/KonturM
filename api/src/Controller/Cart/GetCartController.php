<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Service\CartService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class GetCartController
{
    public function __construct(private readonly CartService $cartService)
    {
    }

    public function __invoke(Request $request): array
    {
        return $this->cartService->getCart();
    }
}

