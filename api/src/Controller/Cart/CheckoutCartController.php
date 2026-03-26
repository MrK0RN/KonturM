<?php

declare(strict_types=1);

namespace App\Controller\Cart;

use App\Service\CartService;
use App\Service\OrderService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
final class CheckoutCartController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): array
    {
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        $orderInput = $this->cartService->buildOrderInputFromCart($payload);
        if (\count($orderInput->items) === 0) {
            throw new BadRequestHttpException('Cart is empty.');
        }

        $violations = $this->validator->validate($orderInput);
        if (\count($violations) > 0) {
            throw new BadRequestHttpException((string) $violations);
        }

        $order = $this->orderService->createOrder($orderInput);
        $this->cartService->clear();

        return [
            'order_number' => $order->getOrderNumber(),
            'status' => $order->getStatus(),
        ];
    }
}

